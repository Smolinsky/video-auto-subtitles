<?php

namespace App\Services\Video;

use App\Collections\Video\TranscriptSegmentCollection;
use App\Dto\Video\TranscriptResultDto;
use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;

readonly class LocalWhisperTranscriber
{
    public function transcribe(Video $video): TranscriptResultDto
    {
        if ($video->audioPath === null) {
            throw new RuntimeException('Audio file is missing. Cannot run local Whisper transcription.');
        }

        $audioPath = Storage::disk($video->audioDisk ?? config('subtitles.storageDisk', 'local'))->path($video->audioPath);
        $pythonBinary = (string) config('subtitles.localWhisper.pythonBinary', 'python3');
        $scriptPath = trim((string) config('subtitles.localWhisper.scriptPath', ''));
        $scriptPath = $scriptPath !== '' ? $scriptPath : base_path('scripts/transcribe_audio.py');

        if (! is_file($scriptPath)) {
            throw new RuntimeException("Local Whisper script was not found at [{$scriptPath}].");
        }

        $command = [
            $pythonBinary,
            $scriptPath,
            '--input',
            $audioPath,
            '--backend',
            (string) config('subtitles.localWhisper.backend', 'whisper'),
            '--model',
            (string) config('subtitles.localWhisper.model', 'base'),
            '--device',
            (string) config('subtitles.localWhisper.device', 'auto'),
            '--compute-type',
            (string) config('subtitles.localWhisper.computeType', 'auto'),
            '--beam-size',
            (string) config('subtitles.localWhisper.beamSize', 5),
        ];

        $language = config('subtitles.transcription.language');

        if (is_string($language) && trim($language) !== '') {
            $command[] = '--language';
            $command[] = trim($language);
        }

        $result = Process::timeout((int) config('subtitles.localWhisper.timeoutSeconds', 1200))
            ->run($command);

        if ($result->failed()) {
            throw new RuntimeException(
                trim($result->errorOutput()) !== ''
                    ? trim($result->errorOutput())
                    : 'Local Whisper transcription failed.',
            );
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($result->output(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Local Whisper script returned invalid JSON.', previous: $exception);
        }

        $text = trim((string) ($payload['text'] ?? ''));

        if ($text === '') {
            throw new RuntimeException('Local Whisper returned an empty transcript.');
        }

        $duration = isset($payload['duration']) && is_numeric($payload['duration'])
            ? (float) $payload['duration']
            : null;

        return new TranscriptResultDto(
            provider: 'local',
            model: (string) ($payload['model'] ?? config('subtitles.localWhisper.model', 'base')),
            text: $text,
            language: isset($payload['language']) ? (string) $payload['language'] : null,
            durationSeconds: $duration,
            segments: TranscriptSegmentCollection::fromArray(
                is_array($payload['segments'] ?? null) ? $payload['segments'] : [],
                $text,
                $duration,
            ),
        );
    }
}
