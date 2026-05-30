<?php

namespace App\Collections\Video;

use App\Dto\Video\VideoProcessingDto;
use App\Support\Collections\TypedDtoCollection;

/**
 * @extends TypedDtoCollection<int, VideoProcessingDto>
 */
class VideoProcessingDtoCollection extends TypedDtoCollection
{
    /**
     * @return class-string<VideoProcessingDto>
     */
    protected static function itemClass(): string
    {
        return VideoProcessingDto::class;
    }
}
