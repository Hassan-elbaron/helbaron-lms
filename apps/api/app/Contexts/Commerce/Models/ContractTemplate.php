<?php

namespace App\Contexts\Commerce\Models;

use App\Contexts\Commerce\Database\Factories\ContractTemplateFactory;
use App\Platform\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplate extends Model
{
    /** @use HasFactory<ContractTemplateFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = ['key', 'version', 'title', 'body', 'is_active'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function bodyHash(): string
    {
        return hash('sha256', $this->body);
    }

    protected static function newFactory(): ContractTemplateFactory
    {
        return ContractTemplateFactory::new();
    }
}
