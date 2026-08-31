<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rename the persisted homepage section key `why_helbaron` -> `why_us`.
 *
 * `homepage_sections.key` is a PERSISTED identifier, not a label: the seeder creates the row by it,
 * the admin resource maps it to a heading, and the frontend switches on it to choose a block
 * renderer. So the vendor's name was baked into a structural key on every instance, and it could not
 * be changed by editing content.
 *
 * `why_us` says the same thing about any academy. Reversible, and the frontend accepts BOTH keys
 * (see brand-section-block.tsx) so an instance whose database is ahead of or behind its bundle keeps
 * rendering rather than falling through to an unknown-block branch.
 */
return new class extends Migration
{
    private const OLD_KEY = 'why_helbaron';

    private const NEW_KEY = 'why_us';

    public function up(): void
    {
        $this->rename(self::OLD_KEY, self::NEW_KEY);
    }

    public function down(): void
    {
        $this->rename(self::NEW_KEY, self::OLD_KEY);
    }

    private function rename(string $from, string $to): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        // A row already holding the target key would collide if `key` is unique; leave it alone and
        // rename only the rows that actually need it.
        if (DB::table('homepage_sections')->where('key', $to)->exists()) {
            DB::table('homepage_sections')->where('key', $from)->delete();

            return;
        }

        DB::table('homepage_sections')->where('key', $from)->update(['key' => $to]);
    }
};
