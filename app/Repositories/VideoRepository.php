<?php

namespace App\Repositories;

use App\Contracts\Repositories\VideoRepositoryInterface;
use App\Dto\Video\CreateVideoRecordDto;
use App\Dto\Video\ListVideosDto;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Collection;

class VideoRepository implements VideoRepositoryInterface
{
    /**
     * @return Collection<int, Video>
     */
    public function getList(ListVideosDto $dto): Collection
    {
        return Video::query()
            ->forUser($dto->user)
            ->applyFilters($dto->filters)
            ->latest()
            ->get();
    }

    public function getByUuid(string $uuid, User $user): Video
    {
        return Video::query()
            ->forUser($user)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(CreateVideoRecordDto $dto): Video
    {
        return Video::query()->create([
            'uuid' => $dto->uuid,
            'userId' => $dto->userId,
            'status' => $dto->status,
            'sourceDisk' => $dto->sourceFile->disk,
            'sourcePath' => $dto->sourceFile->path,
            'originalName' => $dto->originalName,
            'mimeType' => $dto->mimeType,
            'sizeBytes' => $dto->sizeBytes,
        ])->refresh();
    }
}
