<?php

namespace App\Platform\Pages\Models;

use App\Platform\Identity\Models\User;
use App\Platform\Pages\Database\Factories\StaticPageFactory;
use App\Platform\Pages\Enums\PageStatus;
use App\Platform\Pages\Enums\TemplateType;
use App\Platform\Shared\Html\HtmlSanitizer;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A structured static CMS page (about / privacy / a custom page …). Addressed by a unique `slug`,
 * rendered with one of the predefined TemplateType layouts, and gated by an editorial PageStatus
 * plus an optional published_at/unpublished_at schedule window. Bilingual title/body/excerpt/SEO are
 * JSON bags ({ en, ar }); `body` HTML is sanitized on every write via the shared HtmlSanitizer.
 *
 * On every update the page snapshots itself into static_page_versions (version history) so any prior
 * state can be restored with rollbackTo(). This is NOT a drag-and-drop builder — the shape is fixed.
 *
 * @property int $id
 * @property string $public_id
 * @property string $slug
 * @property TemplateType $template
 * @property array<string, string> $title
 * @property array<string, string> $body
 * @property array<string, string>|null $excerpt
 * @property string|null $hero_image
 * @property PageStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $unpublished_at
 * @property int $position
 * @property bool $show_in_nav
 * @property array<string, mixed>|null $seo
 * @property int|null $author_id
 * @property int|null $reviewer_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class StaticPage extends Model
{
    /** @use HasFactory<StaticPageFactory> */
    use HasFactory;

    use HasPublicId;
    use SoftDeletes;

    protected $fillable = [
        'slug', 'template', 'title', 'body', 'excerpt', 'hero_image', 'status',
        'published_at', 'unpublished_at', 'position', 'show_in_nav', 'seo',
        'author_id', 'reviewer_id',
    ];

    /** Fields captured in a version snapshot (everything an editor can change). */
    private const VERSIONED_FIELDS = [
        'slug', 'template', 'title', 'body', 'excerpt', 'hero_image', 'status',
        'published_at', 'unpublished_at', 'position', 'show_in_nav', 'seo', 'reviewer_id',
    ];

    protected function casts(): array
    {
        return [
            'template' => TemplateType::class,
            'title' => 'array',
            'body' => 'array',
            'excerpt' => 'array',
            'status' => PageStatus::class,
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'position' => 'integer',
            'show_in_nav' => 'boolean',
            'seo' => 'array',
        ];
    }

    protected static function newFactory(): StaticPageFactory
    {
        return StaticPageFactory::new();
    }

    protected static function booted(): void
    {
        // Sanitize both body locales on every write — the single write-time sanitization point for
        // page HTML (Filament, the API Action, and the seeder all pass through here).
        static::saving(function (StaticPage $page): void {
            $page->sanitizeBody();
        });

        // Snapshot the page into its version history after each update (incl. rollback restores).
        static::updated(function (StaticPage $page): void {
            $page->recordVersion();
        });
    }

    /** @return HasMany<StaticPageVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(StaticPageVersion::class, 'static_page_id')->orderByDesc('version');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Live pages only: Published status, past/absent published_at, and not yet unpublished.
     *
     * @param  Builder<StaticPage>  $query
     * @return Builder<StaticPage>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PageStatus::Published->value)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('unpublished_at')->orWhere('unpublished_at', '>', now()));
    }

    /** Whether THIS instance is currently live (mirrors the published() scope). */
    public function isLive(): bool
    {
        if ($this->status !== PageStatus::Published) {
            return false;
        }

        if ($this->published_at !== null && $this->published_at->isFuture()) {
            return false;
        }

        if ($this->unpublished_at !== null && ! $this->unpublished_at->isFuture()) {
            return false;
        }

        return true;
    }

    /** Publish now: set status Published and stamp published_at (clearing any prior unpublish). */
    public function publish(): static
    {
        $this->forceFill([
            'status' => PageStatus::Published,
            'published_at' => $this->published_at ?? now(),
            'unpublished_at' => null,
        ])->save();

        return $this;
    }

    /**
     * Snapshot the current page fields into static_page_versions as the next sequential version.
     * Append-only; never overwrites an existing version row.
     */
    public function recordVersion(): StaticPageVersion
    {
        $next = (int) $this->versions()->max('version') + 1;

        $authId = auth()->id();

        return $this->versions()->create([
            'version' => $next,
            'snapshot' => $this->versionSnapshot(),
            'author_id' => $authId !== null ? (int) $authId : $this->author_id,
            'created_at' => now(),
        ]);
    }

    /**
     * Restore the fields from a prior version snapshot. Saving triggers a fresh version record, so
     * a rollback is itself captured in history (history is never rewritten).
     */
    public function rollbackTo(int $version): static
    {
        $snapshot = $this->versions()->where('version', $version)->firstOrFail();

        /** @var array<string, mixed> $data */
        $data = $snapshot->snapshot;

        $this->fill(array_intersect_key($data, array_flip(self::VERSIONED_FIELDS)));
        $this->save();

        return $this;
    }

    /**
     * The subset of fields captured in a version snapshot.
     *
     * @return array<string, mixed>
     */
    private function versionSnapshot(): array
    {
        $out = [];
        foreach (self::VERSIONED_FIELDS as $field) {
            $value = $this->getAttribute($field);
            $out[$field] = $value instanceof \BackedEnum ? $value->value : ($value instanceof Carbon ? $value->toIso8601String() : $value);
        }

        return $out;
    }

    /** Sanitize the body HTML for every locale in-place. */
    private function sanitizeBody(): void
    {
        $body = $this->getAttribute('body');

        if (! is_array($body)) {
            return;
        }

        $sanitizer = app(HtmlSanitizer::class);

        foreach ($body as $locale => $html) {
            if (is_string($html)) {
                $body[$locale] = $sanitizer->sanitize($html);
            }
        }

        $this->setAttribute('body', $body);
    }
}
