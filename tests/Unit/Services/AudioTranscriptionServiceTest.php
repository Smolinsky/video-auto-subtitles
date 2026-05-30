<?php

namespace Tests\Unit\Services;

use App\Models\Video;
use App\Services\Video\AudioTranscriptionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AudioTranscriptionServiceTest extends TestCase
{
    public function test_it_falls_back_to_local_whisper_when_openai_fails(): void
    {
        Storage::fake('local');

        config([
            'services.openai.api_key' => 'test-key',
            'subtitles.transcription.driver' => 'auto',
            'subtitles.transcription.preferredDriver' => 'openai',
            'subtitles.transcription.fallbackDrivers' => ['local'],
            'subtitles.localWhisper.scriptPath' => base_path('scripts/transcribe_audio.py'),
        ]);

        $video = Video::factory()->make([
            'userId' => 1,
            'audioDisk' => 'local',
            'audioPath' => 'videos/test/audio/track.mp3',
        ]);

        Storage::disk('local')->put($video->audioPath, 'fake-audio');

        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'OpenAI is unavailable.',
                ],
            ], 500),
        ]);

        Process::fake([
            '*' => Process::result(json_encode([
                'text' => 'fallback transcript',
                'language' => 'en',
                'duration' => 2.4,
                'model' => 'base',
                'segments' => [
                    [
                        'start' => 0.0,
                        'end' => 2.4,
                        'text' => 'fallback transcript',
                    ],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $result = app(AudioTranscriptionService::class)->transcribe($video);

        $this->assertSame('local', $result->provider);
        $this->assertSame('fallback transcript', $result->text);
        $this->assertCount(1, $result->segments);

        Http::assertSentCount(1);
        Process::assertRanTimes(function ($process) {
            return in_array(base_path('scripts/transcribe_audio.py'), $process->command, true);
        }, 1);
    }
}
