<?php

namespace App\Domains\Certification\Services;

use App\Shared\Services\BaseService;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Throwable;

/**
 * Produces an SVG QR code for a URL. Uses chillerlan/php-qrcode (pure-PHP SVG output, no GD).
 * Falls back to a tiny placeholder SVG if the library/output is unavailable — never throws.
 */
class QrCodeService extends BaseService
{
    public function svgFor(string $url): string
    {
        try {
            if (class_exists(QRCode::class) && class_exists(QRMarkupSVG::class)) {
                $options = new QROptions([
                    'outputInterface' => QRMarkupSVG::class,
                    'outputBase64' => false,
                ]);

                return (string) (new QRCode($options))->render($url);
            }
        } catch (Throwable) {
            // fall through to placeholder
        }

        $safe = htmlspecialchars($url, ENT_QUOTES);

        return '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120"><rect width="120" height="120" fill="#eee"/>'
            ."<text x=\"6\" y=\"64\" font-size=\"6\">{$safe}</text></svg>";
    }

    public function dataUriFor(string $url): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svgFor($url));
    }
}
