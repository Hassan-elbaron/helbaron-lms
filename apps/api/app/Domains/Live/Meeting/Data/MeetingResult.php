<?php

namespace App\Domains\Live\Meeting\Data;

final readonly class MeetingResult
{
    public function __construct(
        public string $provider,
        public string $externalId,
        public string $joinUrl,
        public ?string $hostUrl = null,
    ) {}
}
