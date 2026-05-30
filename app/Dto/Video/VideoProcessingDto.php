<?php

namespace App\Dto\Video;

use App\Collections\Video\TranscriptSegmentCollection;
use App\Enums\VideoProcessingStatus;
use App\Support\LaravelData\Normalizers\ModelAttributesNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class VideoProcessingDto extends Data
{
    public function __construct(
        public string $uuid,
        public VideoProcessingStatus $status,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public StoredFileDto $sourceFile,
        public ?StoredFileDto $audioFile,
        public ?string $transcript,
        public TranscriptSegmentCollection $transcriptSegments,
        public ?string $transcriptProvider,
        public ?string $transcriptModel,
        public ?string $transcriptLanguage,
        public ?float $transcriptDurationSeconds,
        public ?StoredFileDto $srtFile,
        public ?string $failureMessage,
        public bool $audioReady,
        public bool $transcriptReady,
        public bool $srtReady,
        public ?CarbonInterface $audioExtractedAt,
        public ?CarbonInterface $transcriptionStartedAt,
        public ?CarbonInterface $transcribedAt,
        public ?CarbonInterface $srtGeneratedAt,
        public ?CarbonInterface $processingCompletedAt,
        public ?CarbonInterface $createdAt,
        public ?CarbonInterface $updatedAt,
    ) {}

    public static function normalizers(): array
    {
        return [
            ModelAttributesNormalizer::class,
            ...parent::normalizers(),
        ];
    }

    public static function prepareForPipeline(array $properties): array
    {
        $transcript = $properties['transcript'] ?? $properties['transcriptText'] ?? null;
        $durationSeconds = $properties['transcriptDurationSeconds'] ?? $properties['durationSeconds'] ?? null;
        $audioPath = $properties['audioFile']['path'] ?? $properties['audioPath'] ?? null;
        $srtPath = $properties['srtFile']['path'] ?? $properties['srtPath'] ?? null;
        $properties = static::normalizeDateProperties($properties);

        return [
            ...$properties,
            'sourceFile' => $properties['sourceFile'] ?? [
                'disk' => $properties['sourceDisk'] ?? null,
                'path' => $properties['sourcePath'] ?? null,
            ],
            'audioFile' => $properties['audioFile'] ?? ($audioPath ? [
                'disk' => $properties['audioDisk'] ?? null,
                'path' => $audioPath,
            ] : null),
            'transcript' => $transcript,
            'transcriptSegments' => TranscriptSegmentCollection::fromArray(
                $properties['transcriptSegments'] ?? $properties['segments'] ?? null,
                $transcript,
                is_numeric($durationSeconds) ? (float) $durationSeconds : null,
            ),
            'srtFile' => $properties['srtFile'] ?? ($srtPath ? [
                'disk' => $properties['srtDisk'] ?? null,
                'path' => $srtPath,
            ] : null),
            'audioReady' => $properties['audioReady'] ?? filled($audioPath),
            'transcriptReady' => $properties['transcriptReady'] ?? filled($transcript),
            'srtReady' => $properties['srtReady'] ?? filled($srtPath),
        ];
    }

    private static function normalizeDateProperties(array $properties): array
    {
        foreach (['audioExtractedAt', 'transcriptionStartedAt', 'transcribedAt', 'srtGeneratedAt', 'processingCompletedAt', 'createdAt', 'updatedAt'] as $field) {
            if (isset($properties[$field]) && is_string($properties[$field])) {
                $properties[$field] = Carbon::parse($properties[$field]);
            }
        }

        return $properties;
    }
}
