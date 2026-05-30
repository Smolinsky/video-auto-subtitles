<?php

namespace App\Dto\Video;

use Spatie\LaravelData\Data;

class StoredFileDto extends Data
{
    public function __construct(
        public string $disk,
        public string $path,
    ) {}
}
