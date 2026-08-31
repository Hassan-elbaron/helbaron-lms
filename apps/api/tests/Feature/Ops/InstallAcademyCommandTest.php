<?php

use App\Console\Commands\InstallAcademyCommand;
use App\Platform\Branding\Models\BrandSetting;
use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * C1 — `install:academy`: one command from an empty database to a sellable instance.
 *
 * The tests that matter here are the ones about what the command REFUSES to do. Asserting that it
 * seeds roles proves little (db:seed already did that); asserting that it can never publish the
 * vendor's demo catalogue onto a customer's public site is the reason the command exists.
 *
 * Migrations are skipped throughout (--skip-migrations): RefreshDatabase has already migrated, and
 * re-running the migrator inside a transactional test proves nothing about the command.
 */
function installArgs(array $overrides = []): array
{
    return array_merge([
        '--brand' => 'Northwind Academy',
        '--brand-ar' => 'أكاديمية نورثويند',
        '--company' => 'Northwind Education Ltd',
        '--support-email' => 'help@northwind.test',
        '--timezone' => 'Europe/London',
        '--currency' => 'GBP',
        '--skip-admin' => true,
        '--skip-migrations' => true,
        // Laravel's artisan() test helper runs commands INTERACTIVELY, so a command that prompts for
        // a missing value would block here rather than fail. This is also the mode a deploy script or
        // CI job runs in, which is the mode that has to fail loudly on a missing --brand.
        '--no-interaction' => true,
    ], $overrides);
}

/** The private const, read once, so the test asserts the SHIPPED list rather than a copy of it. */
function structuralSeeders(): array
{
    $reflection = new ReflectionClass(InstallAcademyCommand::class);

    return $reflection->getConstant('STRUCTURAL_SEEDERS');
}

it('installs a fresh academy and writes the brand', function (): void {
    $this->artisan('install:academy', installArgs())->assertSuccessful();

    $identity = BrandSetting::current()->identity;

    expect($identity['brand_name']['en'])->toBe('Northwind Academy')
        ->and($identity['brand_name']['ar'])->toBe('أكاديمية نورثويند')
        ->and($identity['company_name'])->toBe('Northwind Education Ltd')
        ->and($identity['support_email'])->toBe('help@northwind.test')
        ->and($identity['timezone'])->toBe('Europe/London')
        ->and($identity['currency'])->toBe('GBP');
});

it('makes the brand reach the branding port, not just the row', function (): void {
    $this->artisan('install:academy', installArgs())->assertSuccessful();

    // Reading the row back only proves the database round-trips. What has to be true is that the
    // value the rest of the application resolves is the academy's.
    app()->forgetInstance(BrandProfilePort::class);
    $profile = app(BrandProfilePort::class)->profile();

    expect($profile->name)->toBe('Northwind Academy')
        ->and($profile->companyName)->toBe('Northwind Education Ltd');
});

/*
 * A defect found by drilling the command against a genuinely empty database rather than by reading
 * it. StaticPagesSeeder, NavigationSeeder, HomepageSeeder and SeoSeeder all resolve the academy name
 * through BrandProfilePort AS THEY WRITE. Branding after seeding therefore produced nine static
 * pages, forty-six nav items and twelve SEO records carrying the config fallback name — and because
 * those seeders are firstOrCreate, no later run would ever correct them. The brand row is now
 * written first.
 */
it('brands the content it seeds, not only the settings row', function (): void {
    config(['branding.name.en' => 'Fallback Brand', 'branding.company_name' => 'Fallback Brand']);

    $this->artisan('install:academy', installArgs())->assertSuccessful();

    $seeded = collect([
        ...DB::table('static_pages')->pluck('title'),
        ...DB::table('static_pages')->pluck('body'),
        ...DB::table('nav_items')->pluck('label'),
        ...DB::table('homepage_sections')->pluck('content'),
        ...DB::table('seo_metas')->pluck('meta_title'),
    ])->map(fn ($value): string => json_encode($value) ?: '')->implode(' ');

    expect($seeded)->not->toContain('Fallback Brand')
        ->and($seeded)->toContain('Northwind Academy');
});

/*
 * Emails resolve their footer and signature from the branding ROW, falling back to
 * config('branding.email.*') -> BRAND_COMPANY_NAME -> BRAND_NAME_EN -> APP_NAME. Without writing
 * them, an academy installed with --brand and no matching environment variables shows one name on
 * the site and another at the bottom of every email it sends.
 */
it('writes an email footer and signature from the brand', function (): void {
    config(['branding.email.footer.en' => 'Fallback Brand', 'branding.email.signature.en' => 'The Fallback Brand Team']);

    $this->artisan('install:academy', installArgs())->assertSuccessful();

    app()->forgetInstance(BrandProfilePort::class);
    $profile = app(BrandProfilePort::class)->profile();

    expect($profile->emailFooter)->toBe('Northwind Education Ltd')
        ->and($profile->emailSignature)->toBe('The Northwind Academy Team');
});

it('leaves an operator-written email footer alone on a re-run', function (): void {
    $this->artisan('install:academy', installArgs())->assertSuccessful();

    $setting = BrandSetting::current();
    $email = $setting->email;
    $email['footer']['en'] = 'Written by hand';
    $setting->update(['email' => $email]);

    $this->artisan('install:academy', installArgs(['--force' => true]))->assertSuccessful();

    expect(BrandSetting::current()->email['footer']['en'])->toBe('Written by hand');
});

it('seeds the roles an administrator needs', function (): void {
    $this->artisan('install:academy', installArgs())->assertSuccessful();

    expect(DB::table('roles')->pluck('name')->all())
        ->toContain('super_admin', 'admin', 'instructor', 'student');
});

/*
 * The whole point of the command. DatabaseSeeder publishes a dozen vendor courses and five invented
 * trainers; running it on a customer instance puts the vendor's demo catalogue on the customer's
 * public site. install:academy must not be able to do that even by accident.
 */
it('publishes no courses, no trainers and no demo accounts', function (): void {
    $this->artisan('install:academy', installArgs())->assertSuccessful();

    expect(DB::table('courses')->count())->toBe(0)
        ->and(DB::table('users')->count())->toBe(0);
});

it('never lists a content seeder among the structural ones', function (): void {
    $forbidden = [
        'CatalogSeeder', 'AuthoringSeeder', 'AssessmentSeeder', 'LearningSeeder', 'CommerceSeeder',
        'CertificationSeeder', 'LiveSeeder', 'CrmSeeder', 'AnalyticsSeeder', 'BlogSeeder',
        'IdentitySeeder', 'DemoSeeder', 'DatabaseSeeder', 'BrandHomepageSeeder',
    ];

    foreach (structuralSeeders() as $class) {
        $short = class_basename($class);

        expect($forbidden)->not->toContain($short);
    }
});

/*
 * The command references its seeders by STRING to stay inside Deptrac's Platform layer, which means
 * neither Deptrac nor the stale-import scanner can see them. This is the check that pays for that
 * choice: a renamed or deleted seeder fails here rather than half-way through a customer install.
 */
it('lists only seeders that exist', function (): void {
    foreach (structuralSeeders() as $class) {
        expect(class_exists($class))->toBeTrue("Structural seeder {$class} does not exist")
            ->and(is_subclass_of($class, Seeder::class))->toBeTrue("{$class} is not a Seeder");
    }
});

it('refuses to run twice once an administrator exists', function (): void {
    $this->artisan('install:academy', installArgs())->assertSuccessful();

    // The install marker is an administrator, so create one the way the command would.
    $this->artisan('identity:create-admin', ['--email' => 'ops@northwind.test', '--name' => 'Ops'])
        ->expectsQuestion('Password (input hidden, min 12 characters)', 'correct-horse-9')
        ->expectsQuestion('Confirm password', 'correct-horse-9')
        ->assertSuccessful();

    $this->artisan('install:academy', installArgs(['--brand' => 'Someone Else']))->assertFailed();

    expect(BrandSetting::current()->identity['brand_name']['en'])->toBe('Northwind Academy');
});

it('re-runs on an installed instance under --force without touching the administrator', function (): void {
    $this->artisan('install:academy', installArgs())->assertSuccessful();
    $this->artisan('identity:create-admin', ['--email' => 'ops@northwind.test', '--name' => 'Ops'])
        ->expectsQuestion('Password (input hidden, min 12 characters)', 'correct-horse-9')
        ->expectsQuestion('Confirm password', 'correct-horse-9')
        ->assertSuccessful();

    $this->artisan('install:academy', installArgs(['--brand' => 'Northwind Group', '--force' => true]))
        ->assertSuccessful();

    expect(BrandSetting::current()->identity['brand_name']['en'])->toBe('Northwind Group')
        ->and(DB::table('users')->where('email', 'ops@northwind.test')->count())->toBe(1);
});

it('converges rather than duplicating when re-run', function (): void {
    $this->artisan('install:academy', installArgs())->assertSuccessful();
    $before = ['roles' => DB::table('roles')->count(), 'static_pages' => DB::table('static_pages')->count()];

    $this->artisan('install:academy', installArgs(['--force' => true]))->assertSuccessful();

    expect(DB::table('roles')->count())->toBe($before['roles'])
        ->and(DB::table('static_pages')->count())->toBe($before['static_pages']);
});

/*
 * Input validation, and specifically that a REJECTED input leaves nothing behind. An operator who
 * mistypes a timezone must not end up with a half-installed instance they then have to reason about.
 */
it('rejects an invalid timezone before writing anything', function (): void {
    $this->artisan('install:academy', installArgs(['--timezone' => 'Mars/Olympus']))->assertFailed();

    expect(DB::table('roles')->count())->toBe(0)
        ->and(BrandSetting::query()->count())->toBe(0);
});

it('rejects an invalid currency, locale and email', function (array $override): void {
    $this->artisan('install:academy', installArgs($override))->assertFailed();

    expect(DB::table('roles')->count())->toBe(0);
})->with([
    'currency' => [['--currency' => 'POUNDS']],
    'locale' => [['--locale' => 'fr']],
    'email' => [['--support-email' => 'not-an-address']],
]);

it('requires a brand name, because there is no safe default for it', function (): void {
    $args = installArgs();
    unset($args['--brand']);

    $this->artisan('install:academy', $args)->assertFailed();
});

it('leaves a hand-set support email alone when re-run without one', function (): void {
    $this->artisan('install:academy', installArgs())->assertSuccessful();

    $args = installArgs(['--force' => true]);
    unset($args['--support-email']);

    $this->artisan('install:academy', $args)->assertSuccessful();

    expect(BrandSetting::current()->identity['support_email'])->toBe('help@northwind.test');
});
