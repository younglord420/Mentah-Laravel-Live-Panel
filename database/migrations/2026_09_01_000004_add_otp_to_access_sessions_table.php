<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            $table->string('otp_code', 6)->nullable()->after('status');
            $table->timestamp('otp_verified_at')->nullable()->after('otp_code');
            $table->unsignedTinyInteger('otp_attempts')->default(0)->after('otp_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            $table->dropColumn(['otp_code', 'otp_verified_at', 'otp_attempts']);
        });
    }
};
