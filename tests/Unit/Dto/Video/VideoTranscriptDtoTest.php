<?php

namespace Tests\Unit\Dto\Video;

use App\Dto\Video\StoredFileDto;
use App\Dto\Video\VideoTranscriptDto;
use App\Enums\VideoProcessingStatus;
use App\Models\Video;
use Carbon\CarbonInterface;
use Tests\TestCase;

final class VideoTranscriptDtoTest extends TestCase
{
    public function test_from_model_hydrates_video_transcript_dto(): void
    {
        $video = new Video;
        $video->forceFill([
            'uuid' => 'video-1',
            'status' => VideoProcessingStatus::TRANSCRIBED,
            'transcriptProvider' => 'openai',
            'transcriptModel' => 'whisper-1',
            'transcriptLanguage' => 'uk',
            'transcriptText' => 'Привіт світ',
            'transcriptSegments' => [
                [
                    'start' => 0.0,
                    'end' => 1.8,
                    'text' => 'Привіт світ',
                ],
            ],
            'transcriptDurationSeconds' => 1.8,
            'srtDisk' => 'local',
            'srtPath' => 'videos/video-1/subtitles/captions.srt',
            'transcriptionStartedAt' => '2026-05-27 12:00:00',
            'transcribedAt' => '2026-05-27 12:00:20',
            'createdAt' => '2026-05-27 11:59:00',
            'updatedAt' => '2026-05-27 12:01:00',
        ]);

        $dto = VideoTranscriptDto::from($video);

        $this->assertSame('video-1', $dto->uuid);
        $this->assertSame(VideoProcessingStatus::TRANSCRIBED, $dto->status);
        $this->assertTrue($dto->transcriptReady);
        $this->assertTrue($dto->srtReady);
        $this->assertSame('openai', $dto->provider);
        $this->assertSame('whisper-1', $dto->model);
        $this->assertSame('uk', $dto->language);
        $this->assertSame(1.8, $dto->durationSeconds);
        $this->assertSame('Привіт світ', $dto->transcript);
        $this->assertCount(1, $dto->segments);
        $this->assertSame(1.8, $dto->segments[0]?->endSeconds);
        $this->assertInstanceOf(StoredFileDto::class, $dto->srtFile);
        $this->assertSame('videos/video-1/subtitles/captions.srt', $dto->srtFile?->path);
        $this->assertInstanceOf(CarbonInterface::class, $dto->transcriptionStartedAt);
        $this->assertSame('openai', $dto->toArray()['provider']);
        $this->assertSame('Привіт світ', $dto->toArray()['segments'][0]['text']);
    }

    public function test_explicit_ready_flags_override_derived_values(): void
    {
        $dto = VideoTranscriptDto::from([
            'uuid' => 'video-2',
            'status' => 'transcribed',
            'transcriptText' => 'Override me',
            'transcriptSegments' => [],
            'transcriptDurationSeconds' => 2.0,
            'srtDisk' => 'local',
            'srtPath' => 'videos/video-2/subtitles/captions.srt',
            'transcriptReady' => false,
            'srtReady' => false,
        ]);

        $this->assertFalse($dto->transcriptReady);
        $this->assertFalse($dto->srtReady);
        $this->assertCount(1, $dto->segments);
        $this->assertSame('Override me', $dto->segments[0]?->text);
    }
}
