<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path', 255);
            $table->string('ip_hash', 64);
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index(['visited_at']);
            $table->index(['path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
