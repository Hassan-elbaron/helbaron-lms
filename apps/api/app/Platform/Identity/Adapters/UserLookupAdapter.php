<?php

namespace App\Platform\Identity\Adapters;

use App\Platform\Identity\Contracts\Data\UserRef;
use App\Platform\Identity\Contracts\UserLookupPort;
use App\Platform\Identity\Models\User;

/**
 * Resolves/lists users as boundary-safe UserRef(s) / scalars. The profile relation is eager-loaded
 * so UserRef can carry avatar/headline without an N+1. Lives inside Identity.
 */
final class UserLookupAdapter implements UserLookupPort
{
    public function refById(int $id): ?UserRef
    {
        return User::query()->with('profile')->find($id)?->toUserRef();
    }

    public function refByPublicId(string $publicId): ?UserRef
    {
        return User::query()->with('profile')->where('public_id', $publicId)->first()?->toUserRef();
    }

    public function idByEmail(string $email): ?int
    {
        $id = User::query()->where('email', $email)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Active users holding the 'instructor' role, ordered by name. Mirrors the existing
     * TrainerController query exactly (is_active + roles.name = 'instructor' + eager profile).
     *
     * @return list<UserRef>
     */
    public function instructors(): array
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->with('profile')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): UserRef => $user->toUserRef())
            ->values()
            ->all();
    }

    public function totalCount(): int
    {
        return User::query()->count();
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, UserRef>
     */
    public function refsByIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $byId = User::query()->with('profile')->whereIn('id', $userIds)->get()->keyBy('id');

        $refs = [];
        foreach ($userIds as $id) {
            $user = $byId->get((int) $id);
            if ($user !== null) {
                $refs[(int) $id] = $user->toUserRef();
            }
        }

        return $refs;
    }
}
