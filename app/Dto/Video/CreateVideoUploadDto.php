<?php

namespace App\Dto\Video;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Data;

class CreateVideoUploadDto extends Data
{
    public function __construct(
        public User $user,
        public UploadedFile $video,
    ) {}
}
