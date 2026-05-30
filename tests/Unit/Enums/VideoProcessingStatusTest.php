<?php

namespace Tests\Unit\Enums;

use App\Enums\VideoProcessingStatus;
use PHPUnit\Framework\TestCase;

final class VideoProcessingStatusTest extends TestCase
{
    public function test_it_returns_a_human_readable_label_for_each_status(): void
    {
        $this->assertSame('Uploaded', VideoProcessingStatus::UPLOADED->label());
        $this->assertSame('Extracting audio', VideoProcessingStatus::EXTRACTING_AUDIO->label());
        $this->assertSame('Audio extracted', VideoProcessingStatus::AUDIO_EXTRACTED->label());
        $this->assertSame('Failed', VideoProcessingStatus::FAILED->label());
    }
}
