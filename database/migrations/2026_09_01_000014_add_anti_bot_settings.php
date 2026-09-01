<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $defaults = [
            'anti_bot_enabled' => '1',
            'anti_bot_mode' => 'redirect',
            'anti_bot_redirect_url' => 'https://www.google.com',
            'anti_bot_strict' => '0',
            'anti_bot_extra_patterns' => '',
        ];

        foreach ($defaults as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'anti_bot_enabled',
            'anti_bot_mode',
            'anti_bot_redirect_url',
            'anti_bot_strict',
            'anti_bot_extra_patterns',
        ])->delete();
    }
};
