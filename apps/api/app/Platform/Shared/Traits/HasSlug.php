<?php

namespace App\Platform\Shared\Traits;

use App\Platform\Shared\Helpers\Slug;
use App\Platform\Shared\Helpers\Uuid;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Auto-generates a URL slug from a source attribute (default `name`) into a `slug` column when the
 * slug is empty, keeps it unique, and remembers every slug the record has ever had.
 *
 * WHAT WAS WRONG BEFORE, all four of them reachable from the admin panel:
 *
 * 1. NO UNIQUENESS. It called `Slug::make()` and not the `Slug::unique()` helper sitting ten lines
 *    below it. `products.slug` and `courses.slug` carry a DB unique index, so two records with the
 *    same title produced a raw SQLSTATE[23505] — a 500, not a validation error. Filament's
 *    `->unique(ignoreRecord: true)` only validates a value that was SUBMITTED, so the blank-field
 *    path — the exact flow this auto-fill exists to serve — walked straight past it.
 * 2. NO ASCII FALLBACK. `Str::slug($value, '-', 'en')` returns '' for a title with no ASCII mapping.
 *    '' went into a NOT NULL UNIQUE column: the first one saved and every subsequent one 500'd.
 *    Measured rather than assumed — Arabic is NOT affected (Laravel transliterates it, as it does
 *    Greek and Cyrillic); CJK, Korean, emoji and punctuation-only titles are. Narrower than it
 *    looks, and still a 500 on the second such record. It now falls back to `public_id`.
 * 3. EMPTY-VS-ABSENT, again. `$translations[$default] ?? reset($translations)` does not fire when
 *    the `en` key EXISTS and is empty — the overwhelmingly common shape for a form that submits
 *    every locale field — so the Arabic title was never reached and the slug was never generated.
 *    Now uses filled() semantics, the same fix as the BRAND_* env keys.
 * 4. NO GUARD AT THE ONLY LEVEL THAT COUNTS. Between the SELECT that checks a candidate and the
 *    INSERT that uses it, another request can take it. The database index is the sole real
 *    authority, so a 23505 naming the slug column is caught and retried with the next suffix.
 *
 * And the behaviour the defect list did not cover: a course slug is the primary public URL, so
 * renaming one used to 404 every inbound link. Old slugs are now recorded in `slug_history`, which
 * both serves redirects and stops the retired slug being reissued to a different record.
 */
trait HasSlug
{
    /** Bounded so a genuinely stuck condition surfaces instead of spinning. */
    private const MAX_SLUG_RETRIES = 3;

    /**
     * Read and written through the query builder rather than an Eloquent model, deliberately.
     *
     * This trait is compiled into eight models across five bounded contexts, so an Eloquent model
     * here would be a cross-context static call from every one of them — eight architecture
     * violations for a two-column lookup table that belongs to no context. The builder keeps the
     * table an implementation detail of the trait, which is what it is.
     */
    private const SLUG_HISTORY_TABLE = 'slug_history';

    public static function bootHasSlug(): void
    {
        static::saving(function ($model): void {
            $model->fillSlug();
        });

        // Recorded after the write commits, so a failed save never claims a slug the record does
        // not actually own.
        static::saved(function ($model): void {
            $model->recordPreviousSlug();
        });
    }

    public function slugSource(): string
    {
        return 'name';
    }

    public function slugColumn(): string
    {
        return 'slug';
    }

    /**
     * Retry a unique-violation on the SLUG column, and nothing else.
     *
     * The check-then-insert in fillSlug() is inherently racy: between the SELECT that finds a
     * candidate free and the INSERT that uses it, another request can take it. No amount of checking
     * closes that window — the unique index is the only real authority — so a 23505 naming this
     * model's slug column is caught and retried with the next suffix.
     *
     * The constraint name must mention the slug column before anything is retried. Swallowing an
     * unrelated 23505 (a duplicate public_id, say) would turn a real error into a mystery three
     * attempts later.
     *
     * EACH ATTEMPT RUNS IN ITS OWN TRANSACTION, and that is not incidental. PostgreSQL aborts the
     * ENTIRE transaction on any error: after the failed INSERT, every subsequent statement returns
     * 25P02 "current transaction is aborted" until a rollback. So the retry's own uniqueness SELECT
     * would fail, and the recovery would be worse than the failure. Laravel turns a nested
     * DB::transaction() into a SAVEPOINT, so the failed attempt rolls back to the savepoint and both
     * this retry and any transaction the caller had already opened survive. Found by testing it —
     * the first version of this method looked correct and could not work.
     */
    public function save(array $options = []): bool
    {
        foreach (range(0, self::MAX_SLUG_RETRIES) as $attempt) {
            try {
                return (bool) DB::transaction(fn (): bool => parent::save($options));
            } catch (QueryException $e) {
                if ($attempt >= self::MAX_SLUG_RETRIES || ! $this->isSlugUniqueViolation($e)) {
                    throw $e;
                }

                $this->{$this->slugColumn()} = $this->uniqueSlug(
                    (string) $this->{$this->slugColumn()},
                );
            }
        }

        return false;
    }

    /**
     * Generate the slug when the column is empty, and make whatever is there unique.
     */
    protected function fillSlug(): void
    {
        $column = $this->slugColumn();
        $current = trim((string) ($this->{$column} ?? ''));

        if ($current === '') {
            $current = Slug::make($this->slugSourceValue());
        }

        // Defect 2: no ASCII mapping. public_id is guaranteed present (HasPublicId assigns it in its
        // own saving hook) and unique, so it is a URL that works rather than a collision.
        if ($current === '') {
            $current = $this->slugFallback();
        }

        if ($current === '') {
            return;
        }

        if ($current !== ($this->{$column} ?? null) || ! $this->exists) {
            $current = $this->uniqueSlug($current);
        }

        $this->{$column} = $current;
    }

    /**
     * The text the slug is built from, resolving a translated attribute when the plain one is blank.
     *
     * Trait boot order is not a safe contract between HasSlug and HasTranslations, so the
     * `{source}_i18n` payload is read directly rather than relied upon to have been synchronised.
     */
    protected function slugSourceValue(): string
    {
        $attribute = $this->slugSource();
        $source = trim((string) ($this->{$attribute} ?? ''));

        if ($source !== '') {
            return $source;
        }

        $translations = $this->getAttribute($attribute.'_i18n');

        if (! is_array($translations) || $translations === []) {
            return '';
        }

        $default = (string) config('shared.default_locale', 'en');

        // Defect 3: `?? reset()` does not fire for a key that EXISTS and is empty, which is exactly
        // what a form submitting every locale sends. Take the default locale only when it has
        // content, otherwise the first locale that does.
        $candidate = trim((string) ($translations[$default] ?? ''));

        if ($candidate !== '') {
            return $candidate;
        }

        foreach ($translations as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    /** A slug that is free, checked against both the live column and retired slugs. */
    protected function uniqueSlug(string $candidate): string
    {
        return Slug::unique($candidate, fn (string $slug): bool => $this->slugIsTaken($slug));
    }

    /**
     * Taken by another record of this type, now or at any point in the past.
     *
     * History matters as much as the live column: reissuing a retired slug would silently point
     * every historic link for the old owner at the new one — worse than the 404 it replaced.
     */
    protected function slugIsTaken(string $slug): bool
    {
        $live = static::query()
            ->where($this->slugColumn(), $slug)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()));

        if (method_exists($live->getModel(), 'trashed') || in_array('deleted_at', $this->getDates() ?: [], true)) {
            // A soft-deleted record still owns its slug: restoring it must not collide.
            $live = $live->withoutGlobalScopes();
        }

        if ($live->exists()) {
            return true;
        }

        return DB::table(self::SLUG_HISTORY_TABLE)
            ->where('sluggable_type', static::class)
            ->where('slug', $slug)
            ->when($this->exists, fn ($q) => $q->where('sluggable_id', '!=', $this->getKey()))
            ->exists();
    }

    /**
     * Last resort when the title produces no ASCII at all: the record's own public id.
     *
     * It ASSIGNS the public id when there is not one yet, rather than reading whatever happens to be
     * there. HasPublicId generates it on `creating`, and this runs on `saving` — two listeners on
     * two events whose relative order is a property of trait boot order, not a contract. Reading it
     * blind produced '' on a create, straight into the NOT NULL column this fallback exists to
     * protect. Assigning is safe in both directions: HasPublicId's hook is `if (empty(...))`, so a
     * value set here is left alone.
     */
    protected function slugFallback(): string
    {
        $publicId = trim((string) ($this->getAttribute('public_id') ?? ''));

        if ($publicId === '') {
            $publicId = Uuid::v7();
            $this->setAttribute('public_id', $publicId);
        }

        return $publicId;
    }

    /**
     * Remember the slug this record just stopped using.
     *
     * insertOrIgnore, not insert: the same old slug can be revisited (renamed away and back), and a
     * duplicate history row is meaningless rather than an error worth failing a save over.
     */
    protected function recordPreviousSlug(): void
    {
        $column = $this->slugColumn();
        $previous = $this->getOriginal($column);

        if (! is_string($previous) || $previous === '' || $previous === $this->{$column}) {
            return;
        }

        try {
            DB::table(self::SLUG_HISTORY_TABLE)->insertOrIgnore([
                'sluggable_type' => static::class,
                'sluggable_id' => $this->getKey(),
                'slug' => $previous,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Losing a history row costs a redirect, never the save that produced it.
        }
    }

    private function isSlugUniqueViolation(QueryException $e): bool
    {
        // 23505 = unique_violation (Postgres); 23000 is MySQL/SQLite's integrity-constraint class.
        if (! in_array((string) $e->getCode(), ['23505', '23000'], true)) {
            return false;
        }

        return str_contains(strtolower($e->getMessage()), strtolower($this->slugColumn()));
    }
}
