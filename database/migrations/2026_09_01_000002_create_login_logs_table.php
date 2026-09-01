<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('isp')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('city')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamps();

            $table->index('logged_in_at');
            $table->index('email');
            $table->index('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
