<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trigram indexes for every leading-wildcard search in the product.
 *
 * WHY NONE OF THESE COULD USE AN INDEX BEFORE. A B-tree can only seek on a known prefix, so
 * `LIKE '%term%'` — which is what all four of these do — degrades to a sequential scan over every
 * row, every time. `courses.search_text` is the worst of them: the column was added specifically to
 * be searched and shipped with no index at all. The cost is invisible on a seeded demo catalogue
 * and arrives with the first customer who imports a real one.
 *
 * pg_trgm's GIN operator class indexes three-character substrings, which is what makes an
 * unanchored LIKE seekable.
 *
 * ARABIC. This is a bilingual product and trigram behaviour is not script-neutral, so the choices
 * here are deliberate:
 *
 *  - `courses.search_text` is written by ArabicTextNormalizer — folded for diacritics, alef/ya/
 *    ta-marbuta form, digit script and case — and the query is folded through the SAME normaliser
 *    before the LIKE. Both sides are already normalised, so `gin_trgm_ops` on the raw column is what
 *    the planner can match.
 *  - EVERY index here is on the BARE column, including the `ilike` call sites. That is the opposite
 *    of what it looks like it should be, so it was measured on a 50k-row table rather than reasoned
 *    about:
 *
 *        index on `name`         + `name ILIKE '%x%'`  ->  Bitmap Index Scan   (used)
 *        index on `lower(name)`  + `name ILIKE '%x%'`  ->  Seq Scan            (ignored)
 *
 *    pg_trgm's GIN opclass supports the ILIKE operator (`~~*`) directly, so the bare column is
 *    exactly right; a lower() expression index does not match a bare ILIKE and is silently never
 *    consulted — present, costly to maintain on every write, and useless. The first draft of this
 *    migration used lower() for those four, which would have shipped four dead indexes.
 *
 * Arabic words are short and trigram selectivity on a 3-letter root is lower than on English, so
 * the win is real but smaller; measured numbers for both scripts are in the Phase E report.
 *
 * Reversible: drops the indexes, and leaves the extension alone. Dropping `pg_trgm` on rollback
 * would break any other index or query in the database that depends on it, and an unused extension
 * costs nothing.
 */
return new class extends Migration
{
    /**
     * Index name => the expression it covers.
     *
     * @var array<string, array{table: string, expression: string}>
     */
    private const INDEXES = [
        // The folded catalogue search column. Both sides normalised — see the note above.
        'courses_search_text_trgm' => ['table' => 'courses', 'expression' => 'search_text'],
        // Lexical arm of hybrid search, also normalised on both sides by the canonicalizer.
        'content_embeddings_chunk_text_trgm' => ['table' => 'content_embeddings', 'expression' => 'chunk_text'],
        // ilike call sites. Bare columns — gin_trgm_ops serves `~~*` directly; see the note above
        // for the measurement that says a lower() index here would never be used.
        'crm_leads_name_trgm' => ['table' => 'crm_leads', 'expression' => 'name'],
        'crm_leads_email_trgm' => ['table' => 'crm_leads', 'expression' => 'email'],
        'live_sessions_title_trgm' => ['table' => 'live_sessions', 'expression' => 'title'],
        'live_sessions_description_trgm' => ['table' => 'live_sessions', 'expression' => 'description'],
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        foreach (self::INDEXES as $name => $target) {
            // Skipped rather than failed: a table belonging to a module an instance does not run is
            // absent by design, and a migration must not make the whole deploy fail over it.
            if (! Schema::hasTable($target['table'])) {
                continue;
            }

            DB::statement(sprintf(
                'CREATE INDEX IF NOT EXISTS %s ON %s USING gin (%s gin_trgm_ops)',
                $name,
                $target['table'],
                $target['expression'],
            ));
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (array_keys(self::INDEXES) as $name) {
            DB::statement('DROP INDEX IF EXISTS '.$name);
        }

        // pg_trgm is deliberately NOT dropped: anything else in the database that came to depend on
        // it would break, and an unused extension costs nothing.
    }
};
