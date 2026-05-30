<?php

namespace Tests\Unit\Jobs;

use App\Enums\VideoProcessingStatus;
use App\Jobs\TranscribeVideoJob;
use App\Models\Video;
use App\Services\Video\AudioTranscriptionService;
use App\Services\Video\SrtSubtitleGenerator;
use App\Services\Video\VideoProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TranscribeVideoJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_persists_transcript_and_generates_srt(): void
    {
        Storage::fake('local');

        config([
            'services.openai.api_key' => 'test-key',
            'subtitles.transcription.driver' => 'openai',
            'subtitles.transcription.fallbackDrivers' => [],
        ]);

        $video = Video::factory()->create([
            'status' => VideoProcessingStatus::AUDIO_EXTRACTED,
            'audioDisk' => 'local',
            'audioPath' => 'videos/test/audio/track.mp3',
            'transcriptText' => null,
            'srtPath' => null,
        ]);

        Storage::disk('local')->put($video->audioPath, 'fake-audio');

        Http::fake([
            '*' => Http::response([
                'text' => 'hello world',
                'language' => 'en',
                'duration' => 1.5,
                'segments' => [
                    [
                        'start' => 0.0,
                        'end' => 1.5,
                        'text' => 'hello world',
                    ],
                ],
            ]),
        ]);

        $job = new TranscribeVideoJob($video);
        $job->handle(
            app(VideoProcessingService::class),
            app(AudioTranscriptionService::class),
            app(SrtSubtitleGenerator::class),
        );

        $video->refresh();

        $this->assertSame(VideoProcessingStatus::TRANSCRIBED, $video->status);
        $this->assertSame('hello world', $video->transcriptText);
        $this->assertSame('openai', $video->transcriptProvider);
        $this->assertSame('whisper-1', $video->transcriptModel);
        $this->assertNotNull($video->srtPath);
        $this->assertTrue(Storage::disk('local')->exists($video->srtPath));
        $this->assertStringContainsString('00:00:00,000 --> 00:00:01,500', Storage::disk('local')->get($video->srtPath));
    }
}
