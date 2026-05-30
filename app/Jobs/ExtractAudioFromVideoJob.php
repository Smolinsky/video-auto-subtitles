<?php

namespace App\Jobs;

use App\Enums\VideoProcessingStatus;
use App\Models\Video;
use App\Services\Video\FfmpegAudioExtractor;
use App\Services\Video\VideoProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ExtractAudioFromVideoJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Video $video) {}

    public function handle(
        VideoProcessingService $videoProcessingService,
        FfmpegAudioExtractor $audioExtractor,
    ): void {
        $video = $this->video->fresh();

        if (
            $video === null
            || in_array($video->status, [
                VideoProcessingStatus::AUDIO_EXTRACTED,
                VideoProcessingStatus::TRANSCRIBING,
                VideoProcessingStatus::GENERATING_SRT,
                VideoProcessingStatus::TRANSCRIBED,
            ], true)
        ) {
            return;
        }

        $videoProcessingService->markExtracting($video);

        try {
            $audio = $audioExtractor->extract($video);

            $video = $videoProcessingService->markAudioExtracted($video, $audio);

            TranscribeVideoJob::dispatch($video)
                ->onQueue(config('subtitles.transcription.queue', config('subtitles.queue', 'media')));
        } catch (Throwable $exception) {
            report($exception);

            $videoProcessingService->markFailed($video, $exception->getMessage());
        }
    }
}
