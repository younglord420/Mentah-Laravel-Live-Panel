<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('one_time_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->index();
            $table->unsignedBigInteger('access_session_id')->nullable()->index();
            $table->string('email')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->unique('ip');
        });

        $now = now();
        $defaults = [
            'one_time_enabled' => '0',
            'one_time_redirect_url' => 'https://www.google.com',
            'document_complete_redirect_url' => 'https://www.google.com',
        ];

        foreach ($defaults as $key => $value) {
            $exists = DB::table('settings')->where('key', $key)->exists();
            if (! $exists) {
                DB::table('settings')->insert([
                    'key' => $key,
                    'value' => $value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('one_time_ips');
    }
};
