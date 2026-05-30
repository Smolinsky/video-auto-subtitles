<?php

namespace App\Enums;

enum VideoProcessingStatus: string
{
    case UPLOADED = 'uploaded';
    case EXTRACTING_AUDIO = 'extractingAudio';
    case AUDIO_EXTRACTED = 'audioExtracted';
    case TRANSCRIBING = 'transcribing';
    case GENERATING_SRT = 'generatingSrt';
    case TRANSCRIBED = 'transcribed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::UPLOADED => 'Uploaded',
            self::EXTRACTING_AUDIO => 'Extracting audio',
            self::AUDIO_EXTRACTED => 'Audio extracted',
            self::TRANSCRIBING => 'Transcribing',
            self::GENERATING_SRT => 'Generating subtitles',
            self::TRANSCRIBED => 'Transcribed',
            self::FAILED => 'Failed',
        };
    }
}
