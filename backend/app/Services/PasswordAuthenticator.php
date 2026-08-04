<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class PasswordAuthenticator
{
    public function attempt(string $username, string $password): ?User
    {
        $user = User::query()
            ->where('username', $username)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}
