<?php

namespace App\Domains\Identity\Actions\Auth;

use App\Domains\Identity\Enums\Role;
use App\Domains\Identity\Events\UserRegistered;
use App\Domains\Identity\Models\User;
use App\Shared\Actions\BaseAction;

class RegisterUserAction extends BaseAction
{
    /**
     * @param  array{name: string, email: string, password: string, phone?: ?string, locale?: ?string}  $data
     */
    public function execute(array $data): User
    {
        $user = $this->transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'locale' => $data['locale'] ?? 'en',
                'is_active' => true,
            ]);

            $user->profile()->create([]);
            $user->assignRole(Role::Student->value);

            return $user;
        });

        UserRegistered::dispatch($user);

        return $user;
    }
}
