<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // =========================
            // RELASI USER
            // =========================
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // =========================
            // DATA UTAMA TASK
            // =========================
            $table->string('title');
            $table->string('category')->nullable(); // kategori task
            $table->text('description')->nullable(); // deskripsi manual

            // =========================
            // 🎙️ VOICE RECORD (HASIL TRANSKRIP)
            // =========================
            $table->longText('voice_text')->nullable(); // hasil rekaman suara → teks

            // =========================
            // DEADLINE & STATUS
            // =========================
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'completed'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
