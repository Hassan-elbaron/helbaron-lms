<?php

namespace App\Platform\Pages\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An append-only snapshot of a StaticPage's fields at a point in time. Rows are created by
 * StaticPage::recordVersion() on every page update and are never mutated. The admin version-history
 * relation manager lists these and can restore any of them via StaticPage::rollbackTo().
 *
 * @property int $id
 * @property int $static_page_id
 * @property int $version
 * @property array<string, mixed> $snapshot
 * @property int|null $author_id
 * @property Carbon|null $created_at
 */
class StaticPageVersion extends Model
{
    public $timestamps = false;

    protected $fillable = ['static_page_id', 'version', 'snapshot', 'author_id', 'created_at'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<StaticPage, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(StaticPage::class, 'static_page_id');
    }
}
