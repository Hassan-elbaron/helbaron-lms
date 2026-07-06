<?php

namespace App\Domains\Certification\Models;

use App\Domains\Certification\Database\Factories\CertificateTemplateFactory;
use App\Shared\Traits\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    /** @use HasFactory<CertificateTemplateFactory> */
    use HasFactory;

    use HasPublicId;

    protected $fillable = ['key', 'version', 'name', 'html', 'orientation', 'is_active'];

    protected function casts(): array
    {
        return ['version' => 'integer', 'is_active' => 'boolean'];
    }

    protected static function newFactory(): CertificateTemplateFactory
    {
        return CertificateTemplateFactory::new();
    }
}
