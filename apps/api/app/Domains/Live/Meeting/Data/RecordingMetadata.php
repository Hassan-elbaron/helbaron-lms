<?php

namespace App\Domains\Live\Meeting\Data;

use Carbon\CarbonInterface;

/**
 * Recording METADATA only — HElbaron does not process or store recording media.
 */
final readonly class RecordingMetadata
{
    public function __construct(
        public string $provider,
        public string $externalId,
        public string $status,
        public ?string $url = null,
        public ?int $durationSeconds = null,
        public ?CarbonInterface $recordedAt = null,
    ) {}
}
