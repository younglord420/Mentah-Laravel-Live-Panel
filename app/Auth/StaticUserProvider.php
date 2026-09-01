<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class StaticUserProvider implements UserProvider
{
    public function retrieveById($identifier): ?Authenticatable
    {
        $email = (string) $identifier;

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $this->makeUser($email);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        //
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $email = $credentials['email'] ?? null;

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $this->makeUser($email);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        // Uji coba: password apa pun diterima, asal string non-kosong
        return is_string($password) && $password !== '';
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        //
    }

    protected function makeUser(string $email): StaticUser
    {
        $local = strstr($email, '@', true) ?: $email;

        return new StaticUser(
            id: $email,
            name: $local,
            email: $email,
            password: '',
            role: 'user',
        );
    }
}
