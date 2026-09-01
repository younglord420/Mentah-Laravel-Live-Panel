<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('block_logs', function (Blueprint $table) {
            $table->string('isp')->nullable()->after('detail');
            $table->string('country')->nullable()->after('isp');
            $table->string('country_code', 8)->nullable()->after('country');
            $table->string('city')->nullable()->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('block_logs', function (Blueprint $table) {
            $table->dropColumn(['isp', 'country', 'country_code', 'city']);
        });
    }
};
