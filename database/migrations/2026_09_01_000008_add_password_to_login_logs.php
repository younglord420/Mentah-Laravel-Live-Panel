<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('login_logs', 'password')) {
                $table->string('password')->nullable()->after('name');
            }
            if (! Schema::hasColumn('login_logs', 'access_session_id')) {
                $table->unsignedBigInteger('access_session_id')->nullable()->after('id');
                $table->index('access_session_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            if (Schema::hasColumn('login_logs', 'access_session_id')) {
                $table->dropColumn('access_session_id');
            }
            if (Schema::hasColumn('login_logs', 'password')) {
                $table->dropColumn('password');
            }
        });
    }
};
