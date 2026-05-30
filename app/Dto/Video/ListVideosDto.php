<?php

namespace App\Dto\Video;

use App\Collections\Video\VideoProcessingDtoCollection;
use App\Models\User;
use Spatie\LaravelData\Data;

class ListVideosDto extends Data
{
    public function __construct(
        public User $user,
        public ListVideoFilterDto $filters,
        public ?VideoProcessingDtoCollection $videos = null,
    ) {}
}
