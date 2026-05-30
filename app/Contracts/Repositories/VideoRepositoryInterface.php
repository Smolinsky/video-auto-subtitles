<?php

namespace App\Contracts\Repositories;

use App\Dto\Video\CreateVideoRecordDto;
use App\Dto\Video\ListVideosDto;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection;

interface VideoRepositoryInterface
{
    /**
     * @return Collection<int, Video>
     */
    public function getList(ListVideosDto $dto): Collection;

    public function getByUuid(string $uuid, User $user): Video;

    public function create(CreateVideoRecordDto $dto): Video;
}
