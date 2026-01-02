<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tambah kolom hanya jika belum ada
        if (!Schema::hasColumn('tasks', 'voice_text')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->text('voice_text')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        // Drop kolom hanya jika ada
        if (Schema::hasColumn('tasks', 'voice_text')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('voice_text');
            });
        }
    }
};
