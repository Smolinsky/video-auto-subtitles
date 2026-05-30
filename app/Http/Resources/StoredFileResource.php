<?php

namespace App\Http\Resources;

use App\Dto\Video\StoredFileDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StoredFileDto */
class StoredFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'disk' => $this->disk,
            'path' => $this->path,
        ];
    }
}
