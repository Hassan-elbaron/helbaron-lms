<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

/**
 * Seeds the deterministic, production-safe DEMO dataset (delegating to {@see DemoSeeder}).
 *
 * Hard safety rails:
 *   - REFUSES in the `production` environment, always.
 *   - REFUSES unless demo mode is explicitly enabled (config('demo.enabled') === true).
 *   - `--reset` (destructive purge + reseed) additionally requires config('demo.reset_allowed').
 *
 * The heavy lifting (and all cross-context model access) lives in the seeder, which is idempotent,
 * so this command stays a thin, safe orchestrator.
 */
class SeedDemo extends Command
{
    protected $signature = 'demo:seed {--reset : Purge existing demo-marked records before reseeding}';

    protected $description = 'Seed a rich, deterministic, production-safe demo dataset (idempotent).';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('demo:seed is disabled in the production environment.');

            return self::FAILURE;
        }

        if (config('demo.enabled') !== true) {
            $this->error('Demo mode is disabled. Set DEMO_MODE=true (config demo.enabled) to seed demo data.');

            return self::FAILURE;
        }

        $reset = (bool) $this->option('reset');

        if ($reset && config('demo.reset_allowed') !== true) {
            $this->error('--reset is not allowed. Set DEMO_RESET_ALLOWED=true (config demo.reset_allowed) to permit a destructive reset.');

            return self::FAILURE;
        }

        // Deterministic RNG for reproducible screenshots (the seeder re-seeds too, so either entry
        // point is deterministic).
        $seed = (int) config('demo.seed');
        mt_srand($seed);
        fake()->seed($seed);

        if ($reset) {
            $this->warn('Reset enabled: demo-marked records will be purged before reseeding.');
        }

        $this->info('Seeding demo dataset...');

        /** @var DemoSeeder $seeder */
        $seeder = app(DemoSeeder::class);
        $seeder->setContainer(app());
        $seeder->setCommand($this);

        $summary = $seeder->seedDemo($reset);

        $this->newLine();
        $this->info('Demo dataset ready. Row counts (demo-marked):');
        $this->table(
            ['Area', 'Count'],
            collect($summary)->map(fn (int $count, string $label): array => [$label, $count])->values()->all(),
        );

        return self::SUCCESS;
    }
}
