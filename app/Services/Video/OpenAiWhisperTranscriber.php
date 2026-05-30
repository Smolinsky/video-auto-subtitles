<?php

namespace App\Services\Video;

use App\Collections\Video\TranscriptSegmentCollection;
use App\Dto\Video\TranscriptResultDto;
use App\Models\Video;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

readonly class OpenAiWhisperTranscriber
{
    public function transcribe(Video $video): TranscriptResultDto
    {
        if ($video->audioPath === null) {
            throw new RuntimeException('Audio file is missing. Cannot call OpenAI transcription.');
        }

        $apiKey = (string) config('services.openai.api_key', '');

        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $audioDisk = $video->audioDisk ?? config('subtitles.storageDisk', 'local');
        $audioPath = Storage::disk($audioDisk)->path($video->audioPath);
        $resource = fopen($audioPath, 'r');

        if ($resource === false) {
            throw new RuntimeException('Could not open the extracted audio file for OpenAI transcription.');
        }

        try {
            $response = $this->request()
                ->attach('file', $resource, basename($audioPath))
                ->post($this->endpoint(), $this->payload());
        } finally {
            fclose($resource);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                trim((string) $response->json('error.message')) !== ''
                    ? trim((string) $response->json('error.message'))
                    : 'OpenAI transcription request failed.',
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('OpenAI returned an unexpected response payload.');
        }

        $text = trim((string) ($data['text'] ?? ''));

        if ($text === '') {
            throw new RuntimeException('OpenAI returned an empty transcript.');
        }

        $duration = isset($data['duration']) && is_numeric($data['duration'])
            ? (float) $data['duration']
            : null;

        return new TranscriptResultDto(
            provider: 'openai',
            model: (string) config('subtitles.openai.model', 'whisper-1'),
            text: $text,
            language: isset($data['language']) ? (string) $data['language'] : null,
            durationSeconds: $duration,
            segments: TranscriptSegmentCollection::fromArray(
                is_array($data['segments'] ?? null) ? $data['segments'] : [],
                $text,
                $duration,
            ),
        );
    }

    private function request(): PendingRequest
    {
        $request = Http::timeout((int) config('subtitles.openai.timeoutSeconds', 300))
            ->connectTimeout((int) config('subtitles.openai.connectTimeoutSeconds', 30))
            ->acceptJson()
            ->withToken((string) config('services.openai.api_key', ''));

        $organization = config('services.openai.organization');
        $project = config('services.openai.project');

        if (is_string($organization) && $organization !== '') {
            $request = $request->withHeader('OpenAI-Organization', $organization);
        }

        if (is_string($project) && $project !== '') {
            $request = $request->withHeader('OpenAI-Project', $project);
        }

        return $request;
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/')
            .'/audio/transcriptions';
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return array_filter([
            'model' => (string) config('subtitles.openai.model', 'whisper-1'),
            'response_format' => 'verbose_json',
            'language' => config('subtitles.transcription.language'),
            'prompt' => config('subtitles.transcription.prompt'),
            'temperature' => config('subtitles.openai.temperature'),
        ], static fn (mixed $value): bool => ! ($value === null || $value === ''));
    }
}
