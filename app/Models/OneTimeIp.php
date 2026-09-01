<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OneTimeIp extends Model
{
    protected $fillable = [
        'ip',
        'access_session_id',
        'email',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public static function hasIp(?string $ip): bool
    {
        if (! is_string($ip) || $ip === '') {
            return false;
        }

        return static::query()->where('ip', $ip)->exists();
    }

    public static function remember(string $ip, ?AccessSession $session = null): void
    {
        if ($ip === '') {
            return;
        }

        static::query()->updateOrCreate(
            ['ip' => $ip],
            [
                'access_session_id' => $session?->id,
                'email' => $session?->email,
                'recorded_at' => now(),
            ]
        );
    }
}
