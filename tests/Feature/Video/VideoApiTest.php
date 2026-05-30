<?php

namespace Tests\Feature\Video;

use App\Enums\VideoProcessingStatus;
use App\Jobs\ExtractAudioFromVideoJob;
use App\Jobs\TranscribeVideoJob;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class VideoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_authenticated_user_can_upload_video_and_queue_audio_extraction(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/videos', [
            'video' => UploadedFile::fake()->create('demo-reel.mp4', 8_192, 'video/mp4'),
        ])->assertCreated();

        $video = Video::query()->where('uuid', $response->json('data.uuid'))->firstOrFail();

        $response
            ->assertJsonPath('data.status', 'uploaded')
            ->assertJsonPath('data.originalName', 'demo-reel.mp4')
            ->assertJsonPath('data.audioReady', false);

        $this->assertSame($user->id, $video->userId);
        Storage::disk('local')->assertExists($video->sourcePath);

        Queue::assertPushed(ExtractAudioFromVideoJob::class, fn (ExtractAudioFromVideoJob $job): bool => $job->video->is($video));
    }

    public function test_authenticated_user_can_list_and_view_only_own_videos(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $olderVideo = Video::factory()->for($user)->create([
            'createdAt' => now()->subMinute(),
        ]);

        $latestVideo = Video::factory()->for($user)->create([
            'createdAt' => now(),
        ]);

        $foreignVideo = Video::factory()->for($otherUser)->create();

        Passport::actingAs($user);

        $this->getJson('/api/v1/videos')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.uuid', $latestVideo->uuid)
            ->assertJsonPath('data.1.uuid', $olderVideo->uuid);

        $this->getJson("/api/v1/videos/{$olderVideo->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $olderVideo->uuid);

        $this->getJson("/api/v1/videos/{$foreignVideo->uuid}")
            ->assertNotFound();
    }

    public function test_authenticated_user_can_retry_video_and_requeue_transcription(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Passport::actingAs($user);

        $video = Video::query()->create([
            'uuid' => (string) Str::uuid(),
            'userId' => $user->id,
            'status' => VideoProcessingStatus::AUDIO_EXTRACTED,
            'sourceDisk' => 'local',
            'sourcePath' => "videos/{$user->id}/source/video.mp4",
            'audioDisk' => 'local',
            'audioPath' => "videos/{$user->id}/audio/audio.wav",
            'originalName' => 'retry.mp4',
            'mimeType' => 'video/mp4',
            'sizeBytes' => 1_024,
        ]);

        $this->postJson("/api/v1/videos/{$video->uuid}/retry")
            ->assertOk()
            ->assertJsonPath('data.uuid', $video->uuid)
            ->assertJsonPath('data.status', VideoProcessingStatus::AUDIO_EXTRACTED->value)
            ->assertJsonPath('message', 'Processing restarted');

        Queue::assertPushed(TranscribeVideoJob::class, fn (TranscribeVideoJob $job): bool => $job->video->is($video));
    }
}
