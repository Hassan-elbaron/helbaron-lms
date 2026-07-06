<?php

namespace App\Domains\Certification\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateSetting extends Model
{
    protected $fillable = [
        'issuer_name', 'signature_name', 'signature_title', 'signature_image_path', 'default_template_id',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], ['issuer_name' => (string) config('certification.issuer.name')]);
    }
}
