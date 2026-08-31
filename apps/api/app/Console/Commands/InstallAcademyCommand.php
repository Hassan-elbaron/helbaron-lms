<?php

namespace App\Console\Commands;

use App\Platform\Shared\Branding\Contracts\BrandInstallerPort;
use App\Platform\Shared\Branding\Data\AcademyBrand;
use App\Platform\Shared\Config\ProductionConfigValidator;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * `php artisan install:academy` — takes an empty database to a sellable instance in one command.
 *
 * This exists because standing up a new academy previously meant an operator remembering which of
 * the twenty-six seeders are STRUCTURAL and which publish demo content. Getting that wrong is not a
 * cosmetic mistake: `db:seed` (DatabaseSeeder) publishes a dozen vendor courses and five invented
 * trainers onto a customer's public catalogue. This command runs the structural set and nothing
 * else, so the wrong answer is not reachable.
 *
 * Idempotent by design:
 *  - every seeder it runs is firstOrCreate-keyed, so a second run converges rather than duplicates;
 *  - it refuses to run at all once an administrator exists, which is the observable marker that the
 *    instance is live. `--force` re-runs the structural set on an installed instance (safe: nothing
 *    is deleted and admin creation is skipped unless a new --admin-email is given).
 *
 * SEEDERS ARE REFERENCED BY STRING, NOT BY IMPORT — deliberately. app/Console belongs to Deptrac's
 * `Platform` layer, which may depend only on Shared + IdentityContracts; importing the Identity /
 * Notifications / Homepage / AI seeders would be a new architectural violation, and the rule is that
 * the baseline never grows. A string reference is invisible to both Deptrac and the stale-import
 * scanner, so the cost of that choice is paid back explicitly: assertSeedersExist() verifies every
 * entry resolves to a real Seeder subclass BEFORE any of them runs, and InstallAcademyCommandTest
 * asserts the same list at test time.
 */
class InstallAcademyCommand extends Command
{
    protected $signature = 'install:academy
        {--brand= : Academy brand name (English) — the only value with no safe default}
        {--brand-ar= : Academy brand name (Arabic); defaults to the English name}
        {--company= : Legal entity named on invoices and certificates; defaults to the brand name}
        {--support-email= : Public support address used in emails and on contact surfaces}
        {--locale= : Default locale (en or ar)}
        {--timezone= : IANA timezone, e.g. Asia/Riyadh}
        {--currency= : ISO-4217 currency code, e.g. SAR}
        {--admin-email= : First administrator email (delegated to identity:create-admin)}
        {--admin-name= : First administrator display name}
        {--skip-admin : Do not create an administrator (you must run identity:create-admin later)}
        {--skip-migrations : Do not run pending migrations}
        {--force : Re-run the structural seeders on an instance that is already installed}';

    protected $description = 'Install a new academy: migrate, seed structural data, set the brand, create the first administrator.';

    /**
     * The STRUCTURAL seeders, in dependency order.
     *
     * Structural means: rows an instance cannot function without, all idempotent, none of them
     * content a customer would have to delete before going live. Roles come first because
     * NotificationsSeeder and StaffRoleTemplatesSeeder attach permissions to them; SeoSeeder runs
     * last because it derives sitemap rows from the pages seeded above it.
     *
     * Deliberately ABSENT, and why:
     *   CatalogSeeder, AuthoringSeeder, AssessmentSeeder, LearningSeeder, CommerceSeeder,
     *   CertificationSeeder, LiveSeeder, CrmSeeder, AnalyticsSeeder, BlogSeeder, IdentitySeeder,
     *   DemoSeeder — demo content or demo accounts.
     *   BrandHomepageSeeder — it WIPES homepage_sections before re-inserting, which is correct as a
     *   deliberate re-layout and wrong as an install step on a homepage the customer may have edited.
     *   CommerceTaxSeeder — seeds a Saudi 15% VAT rate. Right for one market and wrong for the next,
     *   so it stays an explicit choice rather than an install-time assumption.
     *
     * @var list<string>
     */
    private const STRUCTURAL_SEEDERS = [
        'App\Platform\Identity\Database\Seeders\RolePermissionSeeder',
        'Database\Seeders\StaffRoleTemplatesSeeder',
        'App\Platform\Notifications\Database\Seeders\NotificationsSeeder',
        'App\Platform\AI\Database\Seeders\AiPromptSeeder',
        'App\Platform\Features\Database\Seeders\FeatureFlagsSeeder',
        'App\Platform\Branding\Database\Seeders\BrandingSeeder',
        'App\Platform\Homepage\Database\Seeders\HomepageSeeder',
        'App\Platform\Navigation\Database\Seeders\NavigationSeeder',
        'App\Platform\Pages\Database\Seeders\StaticPagesSeeder',
        'App\Platform\Seo\Database\Seeders\SeoSeeder',
    ];

    /** Roles whose existence means "this instance is already live". */
    private const ADMIN_ROLES = ['super_admin', 'admin'];

    public function handle(
        Migrator $migrator,
        BrandInstallerPort $installer,
        ProductionConfigValidator $validator,
    ): int {
        $this->newLine();
        $this->info('Installing academy on '.$this->connectionLabel());
        $this->newLine();

        $problem = $this->assertSeedersExist();

        if ($problem !== null) {
            $this->error($problem);

            return self::FAILURE;
        }

        if ($this->alreadyInstalled() && ! $this->option('force')) {
            $this->error('This instance is already installed — an administrator account exists.');
            $this->line('  Re-running the structural seeders is safe; pass --force to do it deliberately.');
            $this->line('  To add another administrator, use: php artisan identity:create-admin');

            return self::FAILURE;
        }

        // Brand values are collected BEFORE anything is written, so an operator who abandons the
        // prompt or mistypes a timezone leaves the database exactly as they found it.
        $brand = $this->collectBrand();

        if ($brand === null) {
            return self::FAILURE;
        }

        if (! $this->option('skip-migrations') && ! $this->runMigrations($migrator)) {
            return self::FAILURE;
        }

        // BEFORE the seeders, not after. StaticPagesSeeder, NavigationSeeder, HomepageSeeder and
        // SeoSeeder all resolve the academy name through BrandProfilePort as they write their rows.
        // Seeding first and branding second produced nine static pages, forty-six nav items and
        // twelve SEO records carrying whatever the config fallback happened to be — permanently,
        // because those seeders are firstOrCreate and never rewrite an existing row. Measured, not
        // reasoned about: a drill install on an empty database produced vendor strings in eight
        // separate columns until this moved.
        $this->writeBrand($installer, $brand);

        if (! $this->runStructuralSeeders()) {
            return self::FAILURE;
        }

        if (! $this->createAdministrator()) {
            return self::FAILURE;
        }

        return $this->validateConfiguration($validator);
    }

    /**
     * Guard against a renamed or deleted seeder. Without this the failure mode is a HALF-installed
     * instance: every seeder before the missing one has already run by the time db:seed throws.
     */
    private function assertSeedersExist(): ?string
    {
        foreach (self::STRUCTURAL_SEEDERS as $class) {
            if (! class_exists($class)) {
                return "Structural seeder {$class} does not exist. The install list is stale — fix it before installing.";
            }
        }

        return null;
    }

    /**
     * True when any administrator exists. Reads the pivot directly rather than the User model:
     * app/Console is Deptrac's Platform layer and may not depend on the Identity implementation.
     * A missing table means a fresh database, which is by definition not installed.
     */
    private function alreadyInstalled(): bool
    {
        try {
            if (! Schema::hasTable('model_has_roles') || ! Schema::hasTable('roles')) {
                return false;
            }

            return DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->whereIn('roles.name', self::ADMIN_ROLES)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Null when a required value is missing or invalid.
     */
    private function collectBrand(): ?AcademyBrand
    {
        $interactive = $this->input->isInteractive();

        $en = trim((string) $this->option('brand'));

        if ($en === '' && $interactive) {
            $en = trim((string) $this->ask('Academy name (English)'));
        }

        if ($en === '') {
            $this->error('--brand is required. It is the only value with no safe default: it names the academy on every page, email and certificate.');

            return null;
        }

        $ar = trim((string) $this->option('brand-ar'));

        if ($ar === '' && $interactive) {
            $ar = trim((string) $this->ask('Academy name (Arabic)', $en));
        }

        $company = trim((string) $this->option('company'));

        if ($company === '' && $interactive) {
            $company = trim((string) $this->ask('Legal company name (invoices, certificates)', $en));
        }

        $email = trim((string) $this->option('support-email'));

        if ($email === '' && $interactive) {
            $email = trim((string) $this->ask('Support email address', ''));
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->error("'{$email}' is not a valid email address.");

            return null;
        }

        if ($email === '') {
            // Blank is allowed but never silent: contact surfaces render empty until it is set.
            $this->warn('No support email given — contact surfaces and email footers render blank until one is set in /admin.');
        }

        $locale = strtolower(trim((string) ($this->option('locale') ?: config('shared.default_locale', 'en'))));

        if (! in_array($locale, ['en', 'ar'], true)) {
            $this->error("Unsupported locale '{$locale}'. Use en or ar.");

            return null;
        }

        $timezone = trim((string) ($this->option('timezone') ?: config('branding.timezone', 'UTC')));

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            $this->error("'{$timezone}' is not a valid IANA timezone (e.g. Asia/Riyadh, Europe/London).");

            return null;
        }

        $currency = strtoupper(trim((string) ($this->option('currency') ?: config('branding.currency', 'SAR'))));

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            $this->error("'{$currency}' is not an ISO-4217 currency code (three letters, e.g. SAR).");

            return null;
        }

        return new AcademyBrand(
            nameEn: $en,
            nameAr: $ar !== '' ? $ar : $en,
            companyName: $company !== '' ? $company : $en,
            supportEmail: $email,
            locale: $locale,
            timezone: $timezone,
            currency: $currency,
        );
    }

    private function runMigrations(Migrator $migrator): bool
    {
        if ($this->pendingMigrationCount($migrator) === 0) {
            $this->components->twoColumnDetail('Schema', 'already up to date');

            return true;
        }

        $migrated = $this->call('migrate', ['--force' => true]) === self::SUCCESS;

        if (! $migrated || $this->pendingMigrationCount($migrator) > 0) {
            $this->error('Migrations did not complete. Run `php artisan migrate --force` and read the error before retrying.');

            return false;
        }

        return true;
    }

    private function pendingMigrationCount(Migrator $migrator): int
    {
        try {
            $files = array_keys($migrator->getMigrationFiles($migrator->paths()));

            if (! $migrator->repositoryExists()) {
                return count($files);
            }

            return count(array_diff($files, $migrator->getRepository()->getRan()));
        } catch (Throwable) {
            // An unreachable database is not "zero pending" — report work outstanding so the caller
            // fails loudly rather than declaring the schema current.
            return 1;
        }
    }

    private function runStructuralSeeders(): bool
    {
        $this->newLine();
        $this->info('Seeding structural data');

        foreach (self::STRUCTURAL_SEEDERS as $class) {
            $short = substr((string) strrchr($class, '\\'), 1);

            if ($this->callSilent('db:seed', ['--class' => $class, '--force' => true]) !== self::SUCCESS) {
                $this->error("  {$short} FAILED");
                $this->line("  Re-run `php artisan db:seed --class=\"{$class}\" --force` to see the error.");

                return false;
            }

            $this->components->twoColumnDetail('  '.$short, 'done');
        }

        return true;
    }

    /**
     * Hands the academy identity to the Branding module and lets it decide what that means in terms
     * of columns and JSON groups. The command names the academy; it does not know the schema.
     */
    private function writeBrand(BrandInstallerPort $installer, AcademyBrand $brand): void
    {
        $installer->install($brand);

        $this->newLine();
        $this->components->twoColumnDetail('Brand', $brand->nameEn.' — '.$brand->currency.', '.$brand->timezone);

        $this->warnAboutEnvResolvedSurfaces($brand);
    }

    /**
     * Not everything can come from the database.
     *
     * The MFA issuer — the name shown inside a learner's authenticator app — is read from
     * config('identity.mfa.issuer'), which resolves IDENTITY_MFA_ISSUER -> BRAND_NAME_EN -> APP_NAME
     * at config-cache time. It has to: it is needed at TOTP enrolment on a path that must not query
     * for a branding row. So if the environment still names something else, say so HERE rather than
     * letting the operator discover it in a screenshot from a customer six weeks later.
     */
    private function warnAboutEnvResolvedSurfaces(AcademyBrand $brand): void
    {
        $issuer = trim((string) config('identity.mfa.issuer'));

        if ($issuer === '' || $issuer === $brand->nameEn) {
            return;
        }

        $this->newLine();
        $this->warn("Authenticator apps will show '{$issuer}', not '{$brand->nameEn}'.");
        $this->line('  That name is read from the environment, not the database, because it is needed');
        $this->line('  before any branding row can be queried. Set it and redeploy:');
        $this->line("    APP_NAME=\"{$brand->nameEn}\"   (or IDENTITY_MFA_ISSUER / BRAND_NAME_EN)");
    }

    private function createAdministrator(): bool
    {
        if ($this->option('skip-admin')) {
            $this->newLine();
            $this->warn('Skipping administrator creation — nobody can sign in to /admin until you run:');
            $this->line('  php artisan identity:create-admin');

            return true;
        }

        if ($this->option('force') && (string) $this->option('admin-email') === '' && $this->alreadyInstalled()) {
            $this->newLine();
            $this->components->twoColumnDetail('Administrator', 'already exists — left untouched');

            return true;
        }

        $arguments = [];

        if (($email = (string) $this->option('admin-email')) !== '') {
            $arguments['--email'] = $email;
        }

        if (($name = (string) $this->option('admin-name')) !== '') {
            $arguments['--name'] = $name;
        }

        $this->newLine();

        // identity:create-admin owns the password policy and prompts hidden; the password is never
        // passed on the command line, so it cannot land in shell history or a process listing.
        if ($this->call('identity:create-admin', $arguments) !== self::SUCCESS) {
            $this->error('Administrator was not created. The instance is seeded and branded but nobody can sign in.');
            $this->line('  Finish with: php artisan identity:create-admin');

            return false;
        }

        return true;
    }

    /**
     * Final gate. In production a configuration problem is fatal: reporting a successful install on
     * an instance that cannot safely serve traffic is the exact failure this command exists to stop.
     * Outside production the same problems are advisory, because "APP_ENV must be production" is not
     * a defect on a staging box.
     */
    private function validateConfiguration(ProductionConfigValidator $validator): int
    {
        $problems = $validator->criticalErrors();

        if ($problems === []) {
            $this->newLine();
            $this->info('Academy installed. Configuration validated.');
            $this->printRemainingSteps();

            return self::SUCCESS;
        }

        $this->newLine();

        if (! app()->isProduction()) {
            $this->warn('Configuration problems that WOULD block a production deployment ('.count($problems).'):');

            foreach ($problems as $problem) {
                $this->line('  - '.$problem);
            }

            $this->newLine();
            $this->info('Academy installed (non-production: the problems above are advisory here).');
            $this->printRemainingSteps();

            return self::SUCCESS;
        }

        $this->error('Academy data is installed, but the configuration is NOT safe to serve:');

        foreach ($problems as $problem) {
            $this->line('  - '.$problem);
        }

        $this->newLine();
        $this->error('Fix these and re-check with `php artisan config:validate` before sending traffic here.');

        return self::FAILURE;
    }

    /**
     * The honest remainder: everything this command cannot do for you, in the order you need it.
     */
    private function printRemainingSteps(): void
    {
        $this->newLine();
        $this->line('Still to do by hand:');
        $this->line('  1. Upload logo, favicon and certificate assets in /admin -> Branding.');
        $this->line('  2. Set payment gateway credentials in the environment, then `php artisan env:validate --production`.');
        $this->line('  3. Set the tax rate for your market (none is assumed).');
        $this->line('  4. Create the first course, or import the customer catalogue.');
        $this->newLine();
    }

    private function connectionLabel(): string
    {
        $connection = (string) config('database.default');

        return $connection.' / '.(string) config("database.connections.{$connection}.database");
    }
}
