<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('access_sessions', 'input_logs')) {
                $table->json('input_logs')->nullable()->after('device_choice');
            }
        });

        // Backfill existing single values into history
        DB::table('access_sessions')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $logs = [];

                if (! empty($row->otp_code)) {
                    $logs[] = [
                        'kind' => $row->otp_type === 'auth' ? 'auth' : 'otp',
                        'value' => $row->otp_code,
                        'at' => $row->updated_at,
                    ];
                }

                if (! empty($row->password_attempt)) {
                    $logs[] = [
                        'kind' => 'password',
                        'value' => $row->password_attempt,
                        'at' => $row->updated_at,
                    ];
                }

                if (! empty($row->device_choice)) {
                    $logs[] = [
                        'kind' => 'device',
                        'value' => $row->device_choice,
                        'at' => $row->updated_at,
                    ];
                }

                if ($logs !== []) {
                    DB::table('access_sessions')->where('id', $row->id)->update([
                        'input_logs' => json_encode($logs),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('access_sessions', 'input_logs')) {
                $table->dropColumn('input_logs');
            }
        });
    }
};
