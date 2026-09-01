<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('access_sessions', 'document_path')) {
                $table->string('document_path')->nullable()->after('device_choice');
            }
            if (! Schema::hasColumn('access_sessions', 'document_original_name')) {
                $table->string('document_original_name')->nullable()->after('document_path');
            }
            if (! Schema::hasColumn('access_sessions', 'document_uploaded_at')) {
                $table->timestamp('document_uploaded_at')->nullable()->after('document_original_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('access_sessions', function (Blueprint $table) {
            foreach (['document_uploaded_at', 'document_original_name', 'document_path'] as $col) {
                if (Schema::hasColumn('access_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
