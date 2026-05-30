<?php

namespace App\Dto\Video;

use App\Collections\Video\TranscriptSegmentCollection;
use App\Enums\VideoProcessingStatus;
use App\Support\LaravelData\Normalizers\ModelAttributesNormalizer;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class VideoTranscriptDto extends Data
{
    public function __construct(
        public string $uuid,
        public VideoProcessingStatus $status,
        public bool $transcriptReady,
        public bool $srtReady,
        public ?string $provider,
        public ?string $model,
        public ?string $language,
        public ?float $durationSeconds,
        public ?string $transcript,
        public TranscriptSegmentCollection $segments,
        public ?StoredFileDto $srtFile,
        public ?string $failureMessage,
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
        $durationSeconds = $properties['durationSeconds'] ?? $properties['transcriptDurationSeconds'] ?? null;
        $srtPath = $properties['srtFile']['path'] ?? $properties['srtPath'] ?? null;
        $properties = static::normalizeDateProperties($properties);

        return [
            ...$properties,
            'provider' => $properties['provider'] ?? $properties['transcriptProvider'] ?? null,
            'model' => $properties['model'] ?? $properties['transcriptModel'] ?? null,
            'language' => $properties['language'] ?? $properties['transcriptLanguage'] ?? null,
            'durationSeconds' => $durationSeconds,
            'transcript' => $transcript,
            'segments' => TranscriptSegmentCollection::fromArray(
                $properties['segments'] ?? $properties['transcriptSegments'] ?? null,
                $transcript,
                is_numeric($durationSeconds) ? (float) $durationSeconds : null,
            ),
            'srtFile' => $properties['srtFile'] ?? ($srtPath ? [
                'disk' => $properties['srtDisk'] ?? null,
                'path' => $srtPath,
            ] : null),
            'transcriptReady' => $properties['transcriptReady'] ?? filled($transcript),
            'srtReady' => $properties['srtReady'] ?? filled($srtPath),
        ];
    }

    private static function normalizeDateProperties(array $properties): array
    {
        foreach (['transcriptionStartedAt', 'transcribedAt', 'srtGeneratedAt', 'processingCompletedAt', 'createdAt', 'updatedAt'] as $field) {
            if (isset($properties[$field]) && is_string($properties[$field])) {
                $properties[$field] = Carbon::parse($properties[$field]);
            }
        }

        return $properties;
    }
}
