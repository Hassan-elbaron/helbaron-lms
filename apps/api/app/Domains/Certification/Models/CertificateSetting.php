<?php

namespace App\Domains\Certification\Models;

use App\Platform\Shared\Branding\Contracts\BrandProfilePort;
use App\Platform\Shared\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $issuer_name
 * @property string|null $signature_image_path
 */
class CertificateSetting extends Model
{
    use HasTranslations;

    protected $fillable = [
        'issuer_name', 'issuer_name_i18n', 'signature_name', 'signature_name_i18n', 'signature_title',
        'signature_title_i18n', 'signature_image_path', 'default_template_id',
    ];

    /** @var array<int, string> */
    protected array $translatable = ['issuer_name_i18n', 'signature_name_i18n', 'signature_title_i18n'];

    protected function casts(): array
    {
        return [
            'issuer_name_i18n' => 'array',
            'signature_name_i18n' => 'array',
            'signature_title_i18n' => 'array',
        ];
    }

    /**
     * The singleton accessor.
     *
     * On first use the issuer defaults to the instance's own branding (identity.company_name) so a
     * freshly provisioned academy issues certificates in its own name. CERTIFICATION_ISSUER still
     * wins when set explicitly, and APP_NAME is the last resort — previously this fell through to a
     * hardcoded, unrelated brand, which printed the wrong academy on every certificate.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], ['issuer_name' => self::defaultIssuerName()]);
    }

    private static function defaultIssuerName(): string
    {
        // config(), not env(). An env() read from application code returns null once `config:cache`
        // has run unless the value also reaches the process as a real environment variable, and this
        // duplicated a key config/certification.php already resolves. One resolution point.
        $configured = trim((string) config('certification.issuer.override', ''));

        if ($configured !== '') {
            return $configured;
        }

        $profile = app(BrandProfilePort::class)->profile();

        foreach ([$profile->companyName, $profile->name] as $candidate) {
            if (trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return (string) config('certification.issuer.name');
    }
}
