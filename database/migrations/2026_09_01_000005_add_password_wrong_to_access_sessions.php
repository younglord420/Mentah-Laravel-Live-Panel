<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            $table->boolean('otp_declined')->default(false)->after('otp_attempts');
            $table->string('password_attempt')->nullable()->after('otp_declined');
            $table->boolean('password_declined')->default(false)->after('password_attempt');
            $table->timestamp('password_verified_at')->nullable()->after('password_declined');
        });
    }

    public function down(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'otp_declined',
                'password_attempt',
                'password_declined',
                'password_verified_at',
            ]);
        });
    }
};
