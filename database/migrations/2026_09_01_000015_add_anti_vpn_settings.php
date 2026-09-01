<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $defaults = [
            'anti_vpn_enabled' => '1',
            'anti_vpn_mode' => 'redirect',
            'anti_vpn_redirect_url' => 'https://www.google.com',
            'anti_vpn_block_proxy' => '1',
            'anti_vpn_block_hosting' => '1',
            'anti_vpn_extra_isp' => '',
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
            'anti_vpn_enabled',
            'anti_vpn_mode',
            'anti_vpn_redirect_url',
            'anti_vpn_block_proxy',
            'anti_vpn_block_hosting',
            'anti_vpn_extra_isp',
        ])->delete();
    }
};
