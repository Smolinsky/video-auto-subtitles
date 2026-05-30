<?php

namespace App\Dto\Video;

use App\Collections\Video\TranscriptSegmentCollection;
use App\Support\LaravelData\Normalizers\ModelAttributesNormalizer;
use Spatie\LaravelData\Data;

class TranscriptResultDto extends Data
{
    public function __construct(
        public string $provider,
        public string $model,
        public string $text,
        public ?string $language,
        public ?float $durationSeconds,
        public TranscriptSegmentCollection $segments,
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
        $text = $properties['text'] ?? $properties['transcriptText'] ?? '';
        $durationSeconds = $properties['durationSeconds'] ?? $properties['transcriptDurationSeconds'] ?? null;

        return [
            ...$properties,
            'provider' => $properties['provider'] ?? $properties['transcriptProvider'] ?? 'local',
            'model' => $properties['model'] ?? $properties['transcriptModel'] ?? 'unknown',
            'text' => $text,
            'language' => $properties['language'] ?? $properties['transcriptLanguage'] ?? null,
            'durationSeconds' => $durationSeconds,
            'segments' => TranscriptSegmentCollection::fromArray(
                $properties['segments'] ?? $properties['transcriptSegments'] ?? null,
                $text,
                is_numeric($durationSeconds) ? (float) $durationSeconds : null,
            ),
        ];
    }
}
