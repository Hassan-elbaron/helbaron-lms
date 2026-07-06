<?php

namespace App\Domains\Live\Meeting\Providers;

use App\Domains\Live\Contracts\MeetingProvider;
use App\Domains\Live\Meeting\Data\MeetingRequest;
use App\Domains\Live\Meeting\Data\MeetingResult;
use App\Domains\Live\Meeting\Data\RecordingMetadata;
use Illuminate\Support\Str;

/**
 * The ONLY meeting provider implemented. No external SDK — produces deterministic fake meeting
 * coordinates for local/test/dev. Recording metadata is not fabricated (returns null).
 */
class FakeMeetingProvider implements MeetingProvider
{
    public function create(MeetingRequest $request): MeetingResult
    {
        $id = 'mtg_'.Str::random(16);

        return new MeetingResult(
            provider: 'fake',
            externalId: $id,
            joinUrl: 'https://meet.fake.local/'.$id,
            hostUrl: 'https://meet.fake.local/'.$id.'?host=1',
        );
    }

    public function recording(string $externalId): ?RecordingMetadata
    {
        // No recordings are processed; a real provider would return metadata only.
        return null;
    }
}
