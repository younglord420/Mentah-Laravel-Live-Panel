<?php

namespace App\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class StaticUser implements Authenticatable
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $role = 'user',
    ) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        // Static users do not persist remember tokens.
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            id: (string) $attributes['id'],
            name: (string) $attributes['name'],
            email: (string) $attributes['email'],
            password: (string) $attributes['password'],
            role: (string) ($attributes['role'] ?? 'user'),
        );
    }
}
