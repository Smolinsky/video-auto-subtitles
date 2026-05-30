<?php

namespace Tests\Unit\Dto\Video;

use App\Dto\Video\VideoProcessingDto;
use App\Enums\VideoProcessingStatus;
use App\Models\Video;
use Carbon\CarbonInterface;
use Tests\TestCase;

final class VideoProcessingDtoTest extends TestCase
{
    public function test_from_model_hydrates_video_payload_and_computes_derived_fields(): void
    {
        $video = new Video;
        $video->forceFill([
            'uuid' => 'video-1',
            'status' => VideoProcessingStatus::TRANSCRIBED,
            'originalName' => 'demo.mp4',
            'mimeType' => 'video/mp4',
            'sizeBytes' => 123_456,
            'sourceDisk' => 'local',
            'sourcePath' => 'videos/video-1/source/video.mp4',
            'audioDisk' => 'local',
            'audioPath' => 'videos/video-1/audio/track.mp3',
            'transcriptProvider' => 'openai',
            'transcriptModel' => 'whisper-1',
            'transcriptLanguage' => 'en',
            'transcriptText' => 'Hello world',
            'transcriptSegments' => [
                [
                    'start' => 0.0,
                    'end' => 1.5,
                    'text' => 'Hello world',
                ],
            ],
            'transcriptDurationSeconds' => 1.5,
            'srtDisk' => 'local',
            'srtPath' => 'videos/video-1/subtitles/captions.srt',
            'createdAt' => '2026-05-27 12:00:00',
            'updatedAt' => '2026-05-27 12:01:00',
        ]);

        $dto = VideoProcessingDto::from($video);

        $this->assertSame('video-1', $dto->uuid);
        $this->assertSame(VideoProcessingStatus::TRANSCRIBED, $dto->status);
        $this->assertSame('local', $dto->sourceFile->disk);
        $this->assertSame('videos/video-1/audio/track.mp3', $dto->audioFile?->path);
        $this->assertSame('Hello world', $dto->transcript);
        $this->assertCount(1, $dto->transcriptSegments);
        $this->assertSame(1.5, $dto->transcriptSegments[0]->endSeconds);
        $this->assertTrue($dto->audioReady);
        $this->assertTrue($dto->transcriptReady);
        $this->assertTrue($dto->srtReady);
        $this->assertInstanceOf(CarbonInterface::class, $dto->createdAt);
        $this->assertSame('transcribed', $dto->toArray()['status']);
        $this->assertSame('local', $dto->toArray()['sourceFile']['disk']);
        $this->assertSame('Hello world', $dto->toArray()['transcriptSegments'][0]['text']);
    }
}
