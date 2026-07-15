<?php

namespace App\Domains\Live\Actions\Registration;

use App\Domains\Live\Enums\AttendanceSource;
use App\Domains\Live\Models\LiveSession;
use App\Domains\Live\Models\SessionAttendance;
use App\Domains\Live\Services\AttendanceValidationService;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Records attendance for a registered participant (idempotent per session+user). Updates
 * left_at/duration when a leave time is supplied.
 */
class RecordAttendanceAction extends BaseAction
{
    public function __construct(private readonly AttendanceValidationService $validator) {}

    /** @param array<string, mixed> $data optional left_at */
    public function executeByUserId(LiveSession $session, int $userId, array $data = []): SessionAttendance
    {
        $this->validator->assertCanAttendByUserId($session, $userId);

        return $this->transaction(function () use ($session, $userId, $data): SessionAttendance {
            $attendance = SessionAttendance::firstOrNew([
                'session_id' => $session->id,
                'user_id' => $userId,
            ]);

            if (! $attendance->exists) {
                $attendance->source = AttendanceSource::SelfJoin->value;
                $attendance->joined_at = now();
            }

            if (! empty($data['left_at'])) {
                $attendance->left_at = $data['left_at'];
                if ($attendance->joined_at !== null) {
                    $attendance->duration_seconds = $attendance->joined_at->diffInSeconds($attendance->left_at);
                }
            }

            $attendance->save();

            return $attendance;
        });
    }
}
