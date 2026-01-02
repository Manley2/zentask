<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Tambah kolom hanya jika belum ada
        if (!Schema::hasColumn('tasks', 'category')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('category')->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        // Drop kolom hanya jika ada
        if (Schema::hasColumn('tasks', 'category')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
