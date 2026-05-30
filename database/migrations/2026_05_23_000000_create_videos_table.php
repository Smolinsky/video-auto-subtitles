<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('userId')->constrained('users')->cascadeOnDelete();
            $table->string('status')->index();
            $table->string('sourceDisk');
            $table->string('sourcePath');
            $table->string('audioDisk')->nullable();
            $table->string('audioPath')->nullable();
            $table->string('originalName');
            $table->string('mimeType')->nullable();
            $table->unsignedBigInteger('sizeBytes');
            $table->text('failureMessage')->nullable();
            $table->timestamp('audioExtractedAt')->nullable();
            $table->timestamp('processingCompletedAt')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();

            $table->index(['userId', 'createdAt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
