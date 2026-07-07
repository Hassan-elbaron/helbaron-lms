<?php

namespace App\Domains\Analytics\Models;

use App\Domains\Analytics\Enums\ExportFormat;
use App\Domains\Analytics\Enums\ExportStatus;
use App\Platform\Identity\Models\User;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    use HasPublicId;

    protected $fillable = ['user_id', 'format', 'status', 'source', 'params', 'file_path', 'row_count', 'completed_at'];

    protected $hidden = ['file_path']; // storage path is never serialized

    protected function casts(): array
    {
        return [
            'format' => ExportFormat::class,
            'status' => ExportStatus::class,
            'params' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === ExportStatus::Completed;
    }
}
