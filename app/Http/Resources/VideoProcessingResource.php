<?php

namespace App\Http\Resources;

use App\Dto\Video\VideoProcessingDto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VideoProcessingDto */
class VideoProcessingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'originalName' => $this->originalName,
            'mimeType' => $this->mimeType,
            'sizeBytes' => $this->sizeBytes,
            'sourceFile' => new StoredFileResource($this->sourceFile),
            'audioFile' => $this->audioFile === null ? null : new StoredFileResource($this->audioFile),
            'transcript' => $this->transcript,
            'transcriptSegments' => TranscriptSegmentResource::collection($this->transcriptSegments->all()),
            'transcriptProvider' => $this->transcriptProvider,
            'transcriptModel' => $this->transcriptModel,
            'transcriptLanguage' => $this->transcriptLanguage,
            'transcriptDurationSeconds' => $this->transcriptDurationSeconds,
            'srtFile' => $this->srtFile === null ? null : new StoredFileResource($this->srtFile),
            'failureMessage' => $this->failureMessage,
            'audioReady' => $this->audioReady,
            'transcriptReady' => $this->transcriptReady,
            'srtReady' => $this->srtReady,
            'audioExtractedAt' => $this->audioExtractedAt,
            'transcriptionStartedAt' => $this->transcriptionStartedAt,
            'transcribedAt' => $this->transcribedAt,
            'srtGeneratedAt' => $this->srtGeneratedAt,
            'processingCompletedAt' => $this->processingCompletedAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
