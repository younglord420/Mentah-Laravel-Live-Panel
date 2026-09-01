<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('settings')->insert([
            [
                'key' => 'fallback_redirect_url',
                'value' => 'https://www.google.com',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'telegram_enabled',
                'value' => '0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'telegram_bot_token',
                'value' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'telegram_chat_id',
                'value' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'telegram_webhook_secret',
                'value' => Str::random(40),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'telegram_default_phone',
                'value' => '0000',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
