<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $defaults = [
            'block_bot_ip_enabled' => '1',
            'block_bot_ip_myip_ms' => '1',
            'block_bot_ip_vastel' => '1',
            'block_bot_ip_mode' => 'redirect',
            'block_bot_ip_redirect_url' => 'https://www.google.com',
            'block_bot_ip_extra' => '',
            'block_bot_ip_count' => '0',
            'block_bot_ip_cidr_count' => '0',
            'block_bot_ip_synced_at' => '',
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
            'block_bot_ip_enabled',
            'block_bot_ip_myip_ms',
            'block_bot_ip_vastel',
            'block_bot_ip_mode',
            'block_bot_ip_redirect_url',
            'block_bot_ip_extra',
            'block_bot_ip_count',
            'block_bot_ip_cidr_count',
            'block_bot_ip_synced_at',
        ])->delete();
    }
};
