<?php

namespace App\Http\Resources;

use App\Dto\Video\TranscriptSegmentDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TranscriptSegmentDto */
class TranscriptSegmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'startSeconds' => $this->startSeconds,
            'endSeconds' => $this->endSeconds,
            'text' => $this->text,
        ];
    }
}
