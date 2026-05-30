<?php

namespace App\Jobs;

use App\Enums\VideoProcessingStatus;
use App\Models\Video;
use App\Services\Video\AudioTranscriptionService;
use App\Services\Video\SrtSubtitleGenerator;
use App\Services\Video\VideoProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TranscribeVideoJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public Video $video) {}

    public function handle(
        VideoProcessingService $videoProcessingService,
        AudioTranscriptionService $audioTranscriptionService,
        SrtSubtitleGenerator $srtSubtitleGenerator,
    ): void {
        $video = $this->video->fresh();

        if ($video === null) {
            return;
        }

        if ($video->audioPath === null) {
            $videoProcessingService->markFailed($video, 'Extracted audio file is missing. Cannot start transcription.');

            return;
        }

        if (
            $video->status === VideoProcessingStatus::TRANSCRIBED
            && $video->srtPath !== null
            && Storage::disk($video->srtDisk ?? config('subtitles.storageDisk', 'local'))->exists($video->srtPath)
        ) {
            return;
        }

        try {
            $transcript = $videoProcessingService->restoreTranscriptResult($video);

            if ($transcript === null) {
                $video = $videoProcessingService->markTranscribing($video);
                $transcript = $audioTranscriptionService->transcribe($video);
            }

            $video = $videoProcessingService->markGeneratingSrt($video, $transcript);

            $srtFile = $srtSubtitleGenerator->generate($video, $transcript);

            $videoProcessingService->markTranscribed($video, $transcript, $srtFile);
        } catch (Throwable $exception) {
            report($exception);

            $videoProcessingService->markFailed($video, $exception->getMessage());
        }
    }
}
