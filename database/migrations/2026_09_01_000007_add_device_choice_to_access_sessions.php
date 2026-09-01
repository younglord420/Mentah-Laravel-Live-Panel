<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('access_sessions', 'device_choice')) {
                $table->string('device_choice', 32)->nullable()->after('password_verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('access_sessions', 'device_choice')) {
                $table->dropColumn('device_choice');
            }
        });
    }
};
