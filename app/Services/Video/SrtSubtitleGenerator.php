<?php

namespace App\Services\Video;

use App\Collections\Video\TranscriptSegmentCollection;
use App\Dto\Video\GeneratedSubtitleFileDto;
use App\Dto\Video\TranscriptResultDto;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

readonly class SrtSubtitleGenerator
{
    public function generate(Video $video, TranscriptResultDto $transcript): GeneratedSubtitleFileDto
    {
        $disk = config('subtitles.storageDisk', 'local');
        $directory = "videos/{$video->uuid}/subtitles";
        $relativePath = "{$directory}/captions.srt";

        Storage::disk($disk)->makeDirectory($directory);
        Storage::disk($disk)->put($relativePath, $this->buildContent($transcript));

        if (! Storage::disk($disk)->exists($relativePath)) {
            throw new RuntimeException('SRT generation finished without creating the subtitle file.');
        }

        return new GeneratedSubtitleFileDto(
            disk: $disk,
            path: $relativePath,
        );
    }

    private function buildContent(TranscriptResultDto $transcript): string
    {
        $blocks = [];

        foreach ($this->normalizeSegments($transcript) as $index => $segment) {
            $blocks[] = implode("\n", [
                (string) ($index + 1),
                sprintf(
                    '%s --> %s',
                    $this->formatTimestamp($segment->startSeconds),
                    $this->formatTimestamp($segment->endSeconds),
                ),
                $this->wrapText($segment->text),
            ]);
        }

        return implode("\n\n", $blocks)."\n";
    }

    private function normalizeSegments(TranscriptResultDto $transcript): TranscriptSegmentCollection
    {
        if ($transcript->segments->isNotEmpty()) {
            return $transcript->segments;
        }

        return TranscriptSegmentCollection::fromArray([], $transcript->text, $transcript->durationSeconds);
    }

    private function wrapText(string $text): string
    {
        $lineLength = max(16, (int) config('subtitles.srt.maxLineLength', 42));

        return wordwrap(trim(preg_replace('/\s+/', ' ', $text) ?? $text), $lineLength, "\n", false);
    }

    private function formatTimestamp(float $seconds): string
    {
        $milliseconds = (int) round(max(0, $seconds) * 1000);
        $hours = intdiv($milliseconds, 3_600_000);
        $milliseconds -= $hours * 3_600_000;
        $minutes = intdiv($milliseconds, 60_000);
        $milliseconds -= $minutes * 60_000;
        $secs = intdiv($milliseconds, 1000);
        $milliseconds -= $secs * 1000;

        return sprintf('%02d:%02d:%02d,%03d', $hours, $minutes, $secs, $milliseconds);
    }
}
