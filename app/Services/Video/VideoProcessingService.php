<?php

namespace App\Services\Video;

use App\Collections\Video\VideoProcessingDtoCollection;
use App\Contracts\Repositories\VideoRepositoryInterface;
use App\Dto\Video\CreateVideoRecordDto;
use App\Dto\Video\CreateVideoUploadDto;
use App\Dto\Video\ExtractedAudioDto;
use App\Dto\Video\GeneratedSubtitleFileDto;
use App\Dto\Video\ListVideosDto;
use App\Dto\Video\StoredFileDto;
use App\Dto\Video\TranscriptResultDto;
use App\Dto\Video\VideoProcessingDto;
use App\Dto\Video\VideoTranscriptDto;
use App\Enums\VideoProcessingStatus;
use App\Jobs\ExtractAudioFromVideoJob;
use App\Jobs\TranscribeVideoJob;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Str;

readonly class VideoProcessingService
{
    public function __construct(
        private VideoRepositoryInterface $videoRepository,
    ) {}

    public function list(ListVideosDto $dto): VideoProcessingDtoCollection
    {
        $list = new ListVideosDto(
            user: $dto->user,
            filters: $dto->filters,
            videos: VideoProcessingDtoCollection::from($this->videoRepository->getList($dto)),
        );

        return $list->videos ?? new VideoProcessingDtoCollection();
    }

    public function create(CreateVideoUploadDto $dto): ?VideoProcessingDto
    {
        $uuid = (string) Str::uuid();
        $disk = config('subtitles.storageDisk', 'local');
        $extension = $dto->video->getClientOriginalExtension() ?: 'mp4';
        $sourcePath = $dto->video->storeAs(
            "videos/{$uuid}/source",
            "video.{$extension}",
            $disk,
        );

        $video = $this->videoRepository->create(new CreateVideoRecordDto(
            uuid: $uuid,
            userId: $dto->user->id,
            status: VideoProcessingStatus::UPLOADED,
            sourceFile: new StoredFileDto(
                disk: $disk,
                path: $sourcePath,
            ),
            originalName: $dto->video->getClientOriginalName(),
            mimeType: ($dto->video->getMimeType() ?? $dto->video->getClientMimeType() ?? 'application/octet-stream'),
            sizeBytes: (int) ($dto->video->getSize() ?? 0),
        ));

        ExtractAudioFromVideoJob::dispatch($video)
            ->onQueue(config('subtitles.queue', 'media'));

        return $video ? VideoProcessingDto::from($video) : null;
    }

    public function getByUuid(string $uuid, User $user): VideoProcessingDto
    {
        $data = $this->findByUuid($uuid, $user);

        return VideoProcessingDto::from($data);
    }

    public function getTranscriptByUuid(string $uuid, User $user): VideoTranscriptDto
    {
        $data = $this->findByUuid($uuid, $user);

        return VideoTranscriptDto::from($data);
    }

    public function retryByUuid(string $uuid, User $user): VideoProcessingDto
    {
        $video = $this->findByUuid($uuid, $user);

        $this->retry($video);

        return VideoProcessingDto::from($video);
    }

    public function markExtracting(Video $video): Video
    {
        $video->forceFill([
            'status' => VideoProcessingStatus::EXTRACTING_AUDIO,
            'failureMessage' => null,
        ])->save();

        return $video->refresh();
    }

    public function markAudioExtracted(Video $video, ExtractedAudioDto $audio): Video
    {
        $video->forceFill([
            'status' => VideoProcessingStatus::AUDIO_EXTRACTED,
            'audioDisk' => $audio->disk,
            'audioPath' => $audio->path,
            'transcriptProvider' => null,
            'transcriptModel' => null,
            'transcriptLanguage' => null,
            'transcriptText' => null,
            'transcriptSegments' => null,
            'transcriptDurationSeconds' => null,
            'srtDisk' => null,
            'srtPath' => null,
            'failureMessage' => null,
            'audioExtractedAt' => now(),
            'transcriptionStartedAt' => null,
            'transcribedAt' => null,
            'srtGeneratedAt' => null,
            'processingCompletedAt' => null,
        ])->save();

        return $video->refresh();
    }

    public function markTranscribing(Video $video): Video
    {
        $video->forceFill([
            'status' => VideoProcessingStatus::TRANSCRIBING,
            'failureMessage' => null,
            'transcriptionStartedAt' => now(),
            'processingCompletedAt' => null,
        ])->save();

        return $video->refresh();
    }

    public function markGeneratingSrt(Video $video, TranscriptResultDto $transcript): Video
    {
        $video->forceFill([
            'status' => VideoProcessingStatus::GENERATING_SRT,
            'transcriptProvider' => $transcript->provider,
            'transcriptModel' => $transcript->model,
            'transcriptLanguage' => $transcript->language,
            'transcriptText' => $transcript->text,
            'transcriptSegments' => $transcript->segments->toArray(),
            'transcriptDurationSeconds' => $transcript->durationSeconds,
            'transcribedAt' => now(),
            'failureMessage' => null,
            'processingCompletedAt' => null,
        ])->save();

        return $video->refresh();
    }

    public function markTranscribed(
        Video $video,
        TranscriptResultDto $transcript,
        GeneratedSubtitleFileDto $srtFile,
    ): Video {
        $video->forceFill([
            'status' => VideoProcessingStatus::TRANSCRIBED,
            'transcriptProvider' => $transcript->provider,
            'transcriptModel' => $transcript->model,
            'transcriptLanguage' => $transcript->language,
            'transcriptText' => $transcript->text,
            'transcriptSegments' => $transcript->segments->toArray(),
            'transcriptDurationSeconds' => $transcript->durationSeconds,
            'srtDisk' => $srtFile->disk,
            'srtPath' => $srtFile->path,
            'srtGeneratedAt' => now(),
            'transcribedAt' => $video->transcribedAt ?? now(),
            'failureMessage' => null,
            'processingCompletedAt' => now(),
        ])->save();

        return $video->refresh();
    }

    public function markFailed(Video $video, string $message): Video
    {
        $video->forceFill([
            'status' => VideoProcessingStatus::FAILED,
            'failureMessage' => Str::limit(trim($message), 2000),
            'processingCompletedAt' => now(),
        ])->save();

        return $video->refresh();
    }

    public function restoreTranscriptResult(Video $video): ?TranscriptResultDto
    {
        $text = trim((string) ($video->transcriptText ?? ''));

        if ($text === '') {
            return null;
        }

        return TranscriptResultDto::from($video);
    }

    public function retry(Video $video): void
    {
        if (in_array($video->status, [VideoProcessingStatus::UPLOADED, VideoProcessingStatus::FAILED, VideoProcessingStatus::EXTRACTING_AUDIO], true) || $video->audioPath === null) {
            ExtractAudioFromVideoJob::dispatch($video)
                ->onQueue(config('subtitles.queue', 'media'));

            return;
        }

        TranscribeVideoJob::dispatch($video)
            ->onQueue(config('subtitles.transcription.queue', config('subtitles.queue', 'media')));
    }

    private function findByUuid(string $uuid, User $user): Video
    {
        return $this->videoRepository->getByUuid($uuid, $user);
    }
}
