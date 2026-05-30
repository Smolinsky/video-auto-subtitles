<?php

namespace Tests\Unit\Dto\Video;

use App\Dto\Video\TranscriptResultDto;
use App\Models\Video;
use Tests\TestCase;

final class TranscriptResultDtoTest extends TestCase
{
    public function test_from_model_accepts_persisted_transcript_keys_and_builds_fallback_segment(): void
    {
        $video = new Video;
        $video->forceFill([
            'transcriptProvider' => 'local',
            'transcriptModel' => 'base',
            'transcriptText' => 'Fallback transcript',
            'transcriptLanguage' => 'uk',
            'transcriptDurationSeconds' => 2.4,
            'transcriptSegments' => [],
        ]);

        $dto = TranscriptResultDto::from($video);

        $this->assertSame('local', $dto->provider);
        $this->assertSame('base', $dto->model);
        $this->assertSame('Fallback transcript', $dto->text);
        $this->assertSame('uk', $dto->language);
        $this->assertSame(2.4, $dto->durationSeconds);
        $this->assertCount(1, $dto->segments);
        $this->assertSame(0.0, $dto->segments[0]->startSeconds);
        $this->assertSame(2.4, $dto->segments[0]->endSeconds);
        $this->assertSame('Fallback transcript', $dto->segments[0]->text);
    }
}
