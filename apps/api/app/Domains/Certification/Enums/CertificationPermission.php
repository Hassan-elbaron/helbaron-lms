<?php

namespace App\Domains\Certification\Enums;

enum CertificationPermission: string
{
    case ManageTemplates = 'certification.templates.manage';
    case ManageCertificates = 'certification.certificates.manage';
    case ManageBadges = 'certification.badges.manage';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $p) => $p->value, self::cases());
    }
}
