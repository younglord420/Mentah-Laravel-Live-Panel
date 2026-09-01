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
        Schema::table('access_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('access_sessions', 'public_token')) {
                $table->string('public_token', 128)->nullable()->unique()->after('token');
            }
        });

        DB::table('access_sessions')->whereNull('public_token')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                $token = rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
                DB::table('access_sessions')->where('id', $row->id)->update([
                    'public_token' => $token,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('access_sessions', 'public_token')) {
                $table->dropColumn('public_token');
            }
        });
    }
};
