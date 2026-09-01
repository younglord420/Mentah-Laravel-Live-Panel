<?php

use App\Support\IspDetector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $defaults = [
            'ipapi_is_api_key' => '',
            'proxycheck_api_key' => '',
            'abuseipdb_api_key' => '',
            'abuseipdb_enabled' => '0',
            'abuseipdb_min_score' => '50',
            'block_isp_enabled' => '1',
            'block_isp_mode' => 'redirect',
            'block_isp_redirect_url' => 'https://www.google.com',
            'block_isp_list' => implode("\n", IspDetector::DEFAULT_BLOCKLIST),
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
            'ipapi_is_api_key',
            'proxycheck_api_key',
            'abuseipdb_api_key',
            'abuseipdb_enabled',
            'abuseipdb_min_score',
            'block_isp_enabled',
            'block_isp_mode',
            'block_isp_redirect_url',
            'block_isp_list',
        ])->delete();
    }
};
