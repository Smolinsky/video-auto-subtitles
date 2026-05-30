<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('transcriptProvider')->nullable()->after('audioPath');
            $table->string('transcriptModel')->nullable()->after('transcriptProvider');
            $table->string('transcriptLanguage', 16)->nullable()->after('transcriptModel');
            $table->longText('transcriptText')->nullable()->after('transcriptLanguage');
            $table->json('transcriptSegments')->nullable()->after('transcriptText');
            $table->decimal('transcriptDurationSeconds', 10, 3)->nullable()->after('transcriptSegments');
            $table->string('srtDisk')->nullable()->after('transcriptDurationSeconds');
            $table->string('srtPath')->nullable()->after('srtDisk');
            $table->timestamp('transcriptionStartedAt')->nullable()->after('audioExtractedAt');
            $table->timestamp('transcribedAt')->nullable()->after('transcriptionStartedAt');
            $table->timestamp('srtGeneratedAt')->nullable()->after('transcribedAt');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn([
                'transcriptProvider',
                'transcriptModel',
                'transcriptLanguage',
                'transcriptText',
                'transcriptSegments',
                'transcriptDurationSeconds',
                'srtDisk',
                'srtPath',
                'transcriptionStartedAt',
                'transcribedAt',
                'srtGeneratedAt',
            ]);
        });
    }
};
