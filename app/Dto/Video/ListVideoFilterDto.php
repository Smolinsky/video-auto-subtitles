<?php

namespace App\Dto\Video;

use Spatie\LaravelData\Data;

class ListVideoFilterDto extends Data
{
    public function __construct(
        public ?string $status = null,
        public ?string $search = null,
    ) {}
}
