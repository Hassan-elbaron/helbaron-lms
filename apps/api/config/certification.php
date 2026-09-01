<?php

use App\Platform\Shared\Support\Env;

/*
 | Certification domain configuration. PDF rendering goes through the PdfGenerator abstraction.
 */
return [
    'pdf' => [
        'provider' => env('CERTIFICATION_PDF_PROVIDER', 'fake'), // fake | browsershot
        'disk' => env('CERTIFICATION_PDF_DISK', 'local'),
        'download_ttl_minutes' => 15,
    ],
    'number' => [
        'prefix' => env('CERTIFICATION_NUMBER_PREFIX', 'CERT'),
    ],
    'verification' => [
        // Public verify path; the frontend renders the result page.
        'path' => 'certificates/verify',
    ],
    'issuer' => [
        // The operator's EXPLICIT issuer override, or '' when they set none.
        //
        // Resolved HERE rather than in CertificateSetting, which used to call
        // env('CERTIFICATION_ISSUER') at request time. Two things were wrong with that: an env()
        // read outside config/ returns null once `config:cache` has run (unless the value also
        // reaches the process as a real environment variable), and it duplicated this key's
        // resolution in a second place that could drift from it.
        'override' => Env::string('CERTIFICATION_ISSUER'),

        // Last-resort fallback only. CertificateSetting::current() seeds the issuer from the admin's
        // branding record (identity.company_name) first, so a fresh instance issues certificates in
        // the customer's own name. Falling back to APP_NAME keeps a misconfigured instance
        // self-consistent instead of printing an unrelated brand on every certificate.
        'name' => Env::string(
            'CERTIFICATION_ISSUER',
            fn (): string => Env::string('APP_NAME', 'Academy'),
        ),
    ],
];
