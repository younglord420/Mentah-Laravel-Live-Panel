<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->nullable()->index();
            $table->string('reason', 32)->index();
            $table->string('detail', 500)->nullable();
            $table->string('isp')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('city')->nullable();
            $table->string('path', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('visited_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
