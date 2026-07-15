<?php

namespace App\Platform\Homepage\Models;

use App\Platform\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An append-only snapshot of a HomepageSection's fields at a point in time. Rows are created by
 * HomepageSection::recordVersion() on every block update and are never mutated. The admin
 * version-history relation manager lists these and can restore any of them via
 * HomepageSection::rollbackTo(). Mirrors App\Platform\Pages\Models\StaticPageVersion.
 *
 * @property int $id
 * @property int $homepage_section_id
 * @property int $version
 * @property array<string, mixed> $snapshot
 * @property int|null $author_id
 * @property Carbon|null $created_at
 */
class HomepageSectionVersion extends Model
{
    public $timestamps = false;

    protected $fillable = ['homepage_section_id', 'version', 'snapshot', 'author_id', 'created_at'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<HomepageSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(HomepageSection::class, 'homepage_section_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
