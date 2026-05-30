<?php

namespace App\Dto\Video;

use App\Enums\VideoProcessingStatus;
use Spatie\LaravelData\Data;

class CreateVideoRecordDto extends Data
{
    public function __construct(
        public string $uuid,
        public int|string $userId,
        public VideoProcessingStatus $status,
        public StoredFileDto $sourceFile,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
    ) {}
}
