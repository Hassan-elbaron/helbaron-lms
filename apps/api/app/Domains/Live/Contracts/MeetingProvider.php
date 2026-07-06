<?php

namespace App\Domains\Live\Contracts;

use App\Domains\Live\Meeting\Data\MeetingRequest;
use App\Domains\Live\Meeting\Data\MeetingResult;
use App\Domains\Live\Meeting\Data\RecordingMetadata;

/**
 * Provider-agnostic meeting integration. Only concrete adapters reference a vendor SDK — and at
 * this stage only the Fake provider exists (no Zoom/Teams/Meet SDKs).
 */
interface MeetingProvider
{
    public function create(MeetingRequest $request): MeetingResult;

    /** Return recording METADATA if the provider exposes it (never media). */
    public function recording(string $externalId): ?RecordingMetadata;
}
