<?php

namespace App\Services\Video;

use App\Dto\Video\TranscriptResultDto;
use App\Models\Video;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

readonly class AudioTranscriptionService
{
    public function __construct(
        private OpenAiWhisperTranscriber $openAiWhisperTranscriber,
        private LocalWhisperTranscriber $localWhisperTranscriber,
    ) {}

    public function transcribe(Video $video): TranscriptResultDto
    {
        $failures = [];

        foreach ($this->resolveDriverSequence() as $driver) {
            try {
                return $this->transcriberFor($driver)->transcribe($video);
            } catch (Throwable $exception) {
                $message = trim($exception->getMessage()) !== ''
                    ? trim($exception->getMessage())
                    : 'Unknown transcription error.';

                $failures[] = sprintf('%s: %s', $driver, Str::limit($message, 500));
            }
        }

        throw new RuntimeException(sprintf(
            'All transcription providers failed. %s',
            implode(' | ', $failures),
        ));
    }

    /**
     * @return array<int, string>
     */
    private function resolveDriverSequence(): array
    {
        $driver = config('subtitles.transcription.driver', 'auto');
        $preferredDriver = config('subtitles.transcription.preferredDriver', 'openai');
        $fallbackDrivers = config('subtitles.transcription.fallbackDrivers', ['local']);
        $knownDrivers = ['openai', 'local'];

        $sequence = match ($driver) {
            'openai', 'local' => [$driver],
            'auto' => [$preferredDriver],
            default => throw new RuntimeException("Unsupported transcription driver [{$driver}]."),
        };

        foreach (Arr::wrap($fallbackDrivers) as $fallbackDriver) {
            $sequence[] = (string) $fallbackDriver;
        }

        if ($driver === 'auto') {
            foreach ($knownDrivers as $knownDriver) {
                $sequence[] = $knownDriver;
            }
        }

        $sequence = array_values(array_unique(array_filter(
            array_map('strval', $sequence),
            static fn (string $item): bool => in_array($item, $knownDrivers, true),
        )));

        if ($sequence === []) {
            throw new RuntimeException('No valid transcription providers are configured.');
        }

        return $sequence;
    }

    private function transcriberFor(string $driver): OpenAiWhisperTranscriber|LocalWhisperTranscriber
    {
        return match ($driver) {
            'openai' => $this->openAiWhisperTranscriber,
            'local' => $this->localWhisperTranscriber,
            default => throw new RuntimeException("Unsupported transcription driver [{$driver}]."),
        };
    }
}
