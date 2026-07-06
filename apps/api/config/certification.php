<?php

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
        'name' => env('CERTIFICATION_ISSUER', 'Core Business Academy'),
    ],
];
