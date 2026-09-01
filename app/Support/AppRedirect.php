<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AppRedirect
{
    public static function fallback(): RedirectResponse
    {
        return Redirect::away(Setting::fallbackRedirectUrl());
    }

    public static function loginEntry(): RedirectResponse
    {
        return Redirect::to(Setting::loginEntryUrl());
    }

    public static function loginEntryUrl(): string
    {
        return Setting::loginEntryUrl();
    }
}
