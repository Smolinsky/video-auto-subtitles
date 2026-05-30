<?php

namespace Database\Factories;

use App\Enums\VideoProcessingStatus;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Video>
 */
final class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'userId' => User::factory(),
            'status' => VideoProcessingStatus::UPLOADED,
            'sourceDisk' => 'local',
            'sourcePath' => "videos/{$uuid}/source/video.mp4",
            'audioDisk' => null,
            'audioPath' => null,
            'transcriptProvider' => null,
            'transcriptModel' => null,
            'transcriptLanguage' => null,
            'transcriptText' => null,
            'transcriptSegments' => null,
            'transcriptDurationSeconds' => null,
            'srtDisk' => null,
            'srtPath' => null,
            'originalName' => 'sample.mp4',
            'mimeType' => 'video/mp4',
            'sizeBytes' => fake()->numberBetween(100_000, 20_000_000),
            'failureMessage' => null,
            'audioExtractedAt' => null,
            'transcriptionStartedAt' => null,
            'transcribedAt' => null,
            'srtGeneratedAt' => null,
            'processingCompletedAt' => null,
        ];
    }
}
