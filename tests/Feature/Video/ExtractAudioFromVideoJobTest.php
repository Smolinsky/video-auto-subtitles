<?php

namespace Tests\Feature\Video;

use App\Enums\VideoProcessingStatus;
use App\Jobs\ExtractAudioFromVideoJob;
use App\Jobs\TranscribeVideoJob;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ExtractAudioFromVideoJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_job_extracts_audio_and_updates_video_status(): void
    {
        Queue::fake();

        $video = Video::factory()->create();
        Storage::disk('local')->put($video->sourcePath, 'fake-video-binary');

        Process::fake(function ($process) {
            $command = $process->command;
            $targetPath = $command[array_key_last($command)];

            file_put_contents($targetPath, 'fake-audio-binary');

            return Process::result();
        });

        app()->call([new ExtractAudioFromVideoJob($video), 'handle']);

        $video->refresh();

        $this->assertSame(VideoProcessingStatus::AUDIO_EXTRACTED, $video->status);
        $this->assertNotNull($video->audioPath);
        Storage::disk('local')->assertExists($video->audioPath);
        Queue::assertPushed(TranscribeVideoJob::class, fn (TranscribeVideoJob $job): bool => $job->video->is($video));

        Process::assertRan(function ($process) use ($video): bool {
            return $process->command[0] === config('subtitles.ffmpegBinary')
                && in_array(Storage::disk('local')->path($video->sourcePath), $process->command, true);
        });
    }

    public function test_job_marks_video_as_failed_when_ffmpeg_returns_error(): void
    {
        $video = Video::factory()->create();
        Storage::disk('local')->put($video->sourcePath, 'fake-video-binary');

        Process::fake([
            '*' => Process::result('', 'ffmpeg binary not found', 1),
        ]);

        app()->call([new ExtractAudioFromVideoJob($video), 'handle']);

        $video->refresh();

        $this->assertSame(VideoProcessingStatus::FAILED, $video->status);
        $this->assertSame('ffmpeg binary not found', $video->failureMessage);
        $this->assertNull($video->audioPath);
    }
}
