<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the vendor's name from PERSISTED DATA.
 *
 * `company_certificate_branding` stored `helbaron_only` and `company_logo_and_helbaron` as column
 * values in two tables, and the column default on `products` was `helbaron_only`. This product ships
 * as a separate deployment per customer academy, so those rows carry another company's name inside
 * every instance's own database — and the strings were compared raw in `CertificatePolicyResolver`
 * and `Certificate`, which made the vendor name load-bearing in logic rather than merely cosmetic.
 *
 * `company_name_only` is untouched: it never named the vendor.
 *
 * REVERSIBLE. down() restores both the rows and the old default exactly, so the pair can be applied,
 * rolled back and re-applied without drift — verified in the Round 3 result document.
 */
return new class extends Migration
{
    /** @var array<string, string> old value => new value */
    private const RENAMES = [
        'helbaron_only' => 'platform_only',
        'company_logo_and_helbaron' => 'company_and_platform',
    ];

    private const TABLES = ['products', 'company_entitlements'];

    public function up(): void
    {
        // Default first: a row inserted between the rewrite and the default change would otherwise
        // be written with the old value and be missed.
        $this->setProductsDefault('platform_only');
        $this->rewrite(self::RENAMES);
    }

    public function down(): void
    {
        $this->setProductsDefault('helbaron_only');
        $this->rewrite(array_flip(self::RENAMES));
    }

    /**
     * @param  array<string, string>  $map
     */
    private function rewrite(array $map): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_certificate_branding')) {
                continue;
            }

            foreach ($map as $from => $to) {
                DB::table($table)
                    ->where('company_certificate_branding', $from)
                    ->update(['company_certificate_branding' => $to]);
            }
        }
    }

    private function setProductsDefault(string $value): void
    {
        if (! Schema::hasColumn('products', 'company_certificate_branding')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) use ($value): void {
            $table->string('company_certificate_branding')->default($value)->change();
        });
    }
};
