<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret, TelegramService $telegram): Response
    {
        if (! hash_equals((string) Setting::get('telegram_webhook_secret'), $secret)) {
            return response('Forbidden', 403);
        }

        $telegram->processUpdate($request->all());

        return response('OK', 200);
    }
}
