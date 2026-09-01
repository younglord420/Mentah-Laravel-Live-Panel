<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('email');
            $table->string('name')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('isp')->nullable();
            $table->string('country')->nullable();
            $table->string('status')->default('waiting'); // waiting|redirected|closed
            $table->text('redirect_url')->nullable();
            $table->timestamp('redirected_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_sessions');
    }
};
