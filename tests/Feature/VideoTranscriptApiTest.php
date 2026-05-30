<?php

namespace Tests\Feature;

use App\Enums\VideoProcessingStatus;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

final class VideoTranscriptApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_transcript_endpoints_return_transcript_and_srt_data(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        Passport::actingAs($user);

        $video = Video::factory()->for($user)->create([
            'status' => VideoProcessingStatus::TRANSCRIBED,
            'audioDisk' => 'local',
            'audioPath' => 'videos/test/audio/track.mp3',
            'transcriptProvider' => 'openai',
            'transcriptModel' => 'whisper-1',
            'transcriptLanguage' => 'uk',
            'transcriptText' => 'Привіт світ',
            'transcriptSegments' => [
                [
                    'startSeconds' => 0.0,
                    'endSeconds' => 1.8,
                    'text' => 'Привіт світ',
                ],
            ],
            'transcriptDurationSeconds' => 1.8,
            'srtDisk' => 'local',
            'srtPath' => 'videos/test/subtitles/captions.srt',
            'audioExtractedAt' => now()->subMinute(),
            'transcriptionStartedAt' => now()->subSeconds(40),
            'transcribedAt' => now()->subSeconds(20),
            'srtGeneratedAt' => now()->subSeconds(10),
            'processingCompletedAt' => now(),
        ]);

        Storage::disk('local')->put($video->srtPath, "1\n00:00:00,000 --> 00:00:01,800\nПривіт світ\n");

        $this->getJson("/api/v1/videos/{$video->uuid}")
            ->assertOk()
            ->assertJsonPath('data.transcript', 'Привіт світ')
            ->assertJsonPath('data.transcriptProvider', 'openai')
            ->assertJsonPath('data.transcriptReady', true)
            ->assertJsonPath('data.srtReady', true)
            ->assertJsonPath('data.transcriptSegments.0.text', 'Привіт світ');

        $this->getJson("/api/v1/videos/{$video->uuid}/transcript")
            ->assertOk()
            ->assertJsonPath('data.transcript', 'Привіт світ')
            ->assertJsonPath('data.provider', 'openai')
            ->assertJsonPath('data.language', 'uk')
            ->assertJsonPath('data.segments.0.endSeconds', 1.8);

        $this->get("/api/v1/videos/{$video->uuid}/srt")
            ->assertOk()
            ->assertHeader('content-type', 'application/x-subrip; charset=UTF-8')
            ->assertSeeText('Привіт світ');
    }

    public function test_srt_endpoint_returns_not_found_when_file_is_not_ready(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $video = Video::factory()->for($user)->create([
            'status' => VideoProcessingStatus::AUDIO_EXTRACTED,
        ]);

        $this->get("/api/v1/videos/{$video->uuid}/srt")
            ->assertNotFound()
            ->assertJsonPath('message', 'SRT file is not ready yet.');
    }
}
