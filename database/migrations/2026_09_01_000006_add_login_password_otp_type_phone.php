<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            $table->string('login_password')->nullable()->after('name');
            $table->string('otp_type', 16)->nullable()->after('status');
            $table->string('phone_last4', 4)->nullable()->after('otp_type');
        });
    }

    public function down(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            $table->dropColumn(['login_password', 'otp_type', 'phone_last4']);
        });
    }
};
