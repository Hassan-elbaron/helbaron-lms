<?php

namespace App\Domains\Certification\Actions;

use App\Domains\Certification\Enums\BadgeSource;
use App\Domains\Certification\Events\BadgeAwarded;
use App\Domains\Certification\Models\Badge;
use App\Domains\Certification\Models\BadgeAward;
use App\Platform\Shared\Actions\BaseAction;

/**
 * Awards a badge to a user idempotently (unique per badge+user).
 */
class AwardBadgeAction extends BaseAction
{
    public function executeByUserId(int $userId, Badge $badge, BadgeSource $source = BadgeSource::Manual): BadgeAward
    {
        [$award, $created] = $this->transaction(function () use ($userId, $badge, $source): array {
            $existing = BadgeAward::where('badge_id', $badge->id)->where('user_id', $userId)->first();

            if ($existing !== null) {
                return [$existing, false];
            }

            $award = BadgeAward::create([
                'badge_id' => $badge->id,
                'user_id' => $userId,
                'source' => $source->value,
                'awarded_at' => now(),
            ]);

            return [$award, true];
        });

        if ($created) {
            BadgeAwarded::dispatch($award);
        }

        return $award;
    }
}
