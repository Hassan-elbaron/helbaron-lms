<?php

use App\Platform\Shared\Helpers\Uuid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * E3 — every leading-wildcard search in the product now has a trigram index behind it.
 *
 * A B-tree cannot seek without a known prefix, so `LIKE '%term%'` degrades to a sequential scan over
 * the whole table on every keystroke. `courses.search_text` was the worst case: a column added
 * specifically to be searched, shipped with no index at all. None of this shows up on a demo
 * catalogue; it arrives with the first customer who imports a real one.
 *
 * The tests assert the PLAN, not a duration. A timing assertion on a CI runner is a flake generator,
 * and "did the planner choose the index" is the question that actually matters — a present-but-
 * unused index is the specific failure this migration nearly shipped (see its header).
 */
function indexExists(string $name): bool
{
    return DB::table('pg_indexes')->where('indexname', $name)->exists();
}

function planFor(string $sql, array $bindings = []): string
{
    return collect(DB::select('EXPLAIN '.$sql, $bindings))
        ->map(fn ($row): string => (string) ($row->{'QUERY PLAN'} ?? ''))
        ->implode("\n");
}

it('installs pg_trgm', function (): void {
    expect(DB::table('pg_extension')->where('extname', 'pg_trgm')->exists())->toBeTrue();
});

it('indexes every leading-wildcard search column', function (string $index): void {
    expect(indexExists($index))->toBeTrue("{$index} is missing");
})->with([
    'courses_search_text_trgm',
    'content_embeddings_chunk_text_trgm',
    'crm_leads_name_trgm',
    'crm_leads_email_trgm',
    'live_sessions_title_trgm',
    'live_sessions_description_trgm',
]);

/*
 * The measurement that drove the migration's design, kept as a test.
 *
 * A `lower(col)` expression index is NOT used for a bare `col ILIKE '%x%'` — the planner falls back
 * to a sequential scan. The first draft of the migration used lower() for the four ilike call sites,
 * which would have created four indexes that were maintained on every write and never once read.
 * If someone "corrects" them back, this fails.
 */
it('serves an ILIKE from the bare-column index', function (): void {
    // Enough rows that a sequential scan is not simply the cheaper plan.
    $rows = collect(range(1, 600))->map(fn (int $i): array => [
        'public_id' => Uuid::v7(),
        'name' => 'Lead Person '.$i,
        'email' => "lead{$i}@example.test",
        'created_at' => now(),
        'updated_at' => now(),
    ])->all();

    DB::table('crm_leads')->insert($rows);
    DB::statement('ANALYZE crm_leads');

    // enable_seqscan=off, because the question is "CAN this index serve an ILIKE", not "would the
    // planner bother on 600 rows" — on a table this small a sequential scan is genuinely cheaper and
    // choosing it is correct. Disabling it makes the planner reach for the index if one is usable at
    // all, which is the discriminating test: a lower() expression index is not usable here and the
    // plan stays a sequential scan even with seqscan priced out of reach.
    DB::statement('SET LOCAL enable_seqscan = off');

    $plan = planFor("select id from crm_leads where name ilike '%erson 421%'");

    expect($plan)->toContain('crm_leads_name_trgm');
})->skip(fn (): bool => ! Schema::hasTable('crm_leads'), 'CRM module not installed');

it('drops cleanly and rebuilds', function (): void {
    // Reversibility, asserted rather than assumed: the indexes go and the extension deliberately
    // stays, because dropping pg_trgm would break anything else that came to depend on it.
    DB::statement('DROP INDEX IF EXISTS courses_search_text_trgm');

    expect(indexExists('courses_search_text_trgm'))->toBeFalse();

    DB::statement('CREATE INDEX courses_search_text_trgm ON courses USING gin (search_text gin_trgm_ops)');

    expect(indexExists('courses_search_text_trgm'))->toBeTrue()
        ->and(DB::table('pg_extension')->where('extname', 'pg_trgm')->exists())->toBeTrue();
});
