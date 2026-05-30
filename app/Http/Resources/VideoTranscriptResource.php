<?php

namespace App\Http\Resources;

use App\Dto\Video\VideoTranscriptDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VideoTranscriptDto */
class VideoTranscriptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'transcriptReady' => $this->transcriptReady,
            'srtReady' => $this->srtReady,
            'provider' => $this->provider,
            'model' => $this->model,
            'language' => $this->language,
            'durationSeconds' => $this->durationSeconds,
            'transcript' => $this->transcript,
            'segments' => TranscriptSegmentResource::collection($this->segments->all()),
            'srtFile' => $this->srtFile === null ? null : new StoredFileResource($this->srtFile),
            'failureMessage' => $this->failureMessage,
            'transcriptionStartedAt' => $this->transcriptionStartedAt,
            'transcribedAt' => $this->transcribedAt,
            'srtGeneratedAt' => $this->srtGeneratedAt,
            'processingCompletedAt' => $this->processingCompletedAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
