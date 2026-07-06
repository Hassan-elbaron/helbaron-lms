<?php

namespace App\Domains\Identity\Actions\Profile;

use App\Domains\Identity\Models\User;
use App\Shared\Actions\BaseAction;

class UpdateProfileAction extends BaseAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): User
    {
        return $this->transaction(function () use ($user, $data): User {
            $user->fill(array_filter([
                'name' => $data['name'] ?? null,
                'locale' => $data['locale'] ?? null,
            ], fn ($v) => $v !== null));
            $user->save();

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                array_filter([
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'bio' => $data['bio'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'date_of_birth' => $data['date_of_birth'] ?? null,
                ], fn ($v) => $v !== null),
            );

            return $user->fresh('profile');
        });
    }
}
