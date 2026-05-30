<?php

namespace App\Services\Video;

use App\Dto\Video\ExtractedAudioDto;
use App\Models\Video;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

readonly class FfmpegAudioExtractor
{
    public function extract(Video $video): ExtractedAudioDto
    {
        $disk = config('subtitles.storageDisk', 'local');
        $extension = config('subtitles.audioExtension', 'mp3');
        $directory = "videos/{$video->uuid}/audio";
        $relativePath = "{$directory}/track.{$extension}";

        Storage::disk($disk)->makeDirectory($directory);

        $sourcePath = Storage::disk($video->sourceDisk)->path($video->sourcePath);
        $targetPath = Storage::disk($disk)->path($relativePath);

        $result = Process::run([
            config('subtitles.ffmpegBinary', 'ffmpeg'),
            '-y',
            '-i',
            $sourcePath,
            '-vn',
            '-acodec',
            config('subtitles.audioCodec', 'libmp3lame'),
            '-ar',
            (string) config('subtitles.audioSampleRate', 16000),
            '-ac',
            (string) config('subtitles.audioChannels', 1),
            $targetPath,
        ]);

        if ($result->failed()) {
            throw new RuntimeException(
                trim($result->errorOutput()) !== ''
                    ? trim($result->errorOutput())
                    : 'FFmpeg could not extract audio from the uploaded video.',
            );
        }

        if (! Storage::disk($disk)->exists($relativePath)) {
            throw new RuntimeException('FFmpeg finished without creating the extracted audio file.');
        }

        return new ExtractedAudioDto(
            disk: $disk,
            path: $relativePath,
        );
    }
}
