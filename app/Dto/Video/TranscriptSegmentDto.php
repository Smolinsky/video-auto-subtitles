<?php

namespace App\Dto\Video;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class TranscriptSegmentDto extends Data
{
    public function __construct(
        #[MapInputName('start')]
        public float $startSeconds,
        #[MapInputName('end')]
        public float $endSeconds,
        public string $text,
    ) {}
}
