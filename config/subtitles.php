<?php

return [
    'storageDisk' => env('SUBTITLES_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),
    'queue' => env('SUBTITLES_QUEUE', 'media'),
    'maxUploadKb' => (int) env('SUBTITLES_MAX_UPLOAD_KB', 102400),
    'ffmpegBinary' => env('FFMPEG_BINARY', 'ffmpeg'),
    'audioCodec' => env('FFMPEG_AUDIO_CODEC', 'libmp3lame'),
    'audioExtension' => env('FFMPEG_AUDIO_EXTENSION', 'mp3'),
    'audioSampleRate' => (int) env('FFMPEG_AUDIO_SAMPLE_RATE', 16000),
    'audioChannels' => (int) env('FFMPEG_AUDIO_CHANNELS', 1),
    'transcription' => [
        'queue' => env('SUBTITLES_TRANSCRIPTION_QUEUE', env('SUBTITLES_QUEUE', 'media')),
        'driver' => env('SUBTITLES_TRANSCRIPTION_DRIVER', 'auto'),
        'preferredDriver' => env('SUBTITLES_TRANSCRIPTION_PREFERRED_DRIVER', 'openai'),
        'fallbackDrivers' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('SUBTITLES_TRANSCRIPTION_FALLBACK_DRIVERS', 'local')),
        ))),
        'language' => env('SUBTITLES_TRANSCRIPTION_LANGUAGE'),
        'prompt' => env('SUBTITLES_TRANSCRIPTION_PROMPT'),
    ],
    'openai' => [
        'model' => env('OPENAI_AUDIO_TRANSCRIPTION_MODEL', 'whisper-1'),
        'temperature' => env('OPENAI_AUDIO_TRANSCRIPTION_TEMPERATURE'),
        'timeoutSeconds' => (int) env('OPENAI_AUDIO_TRANSCRIPTION_TIMEOUT', 300),
        'connectTimeoutSeconds' => (int) env('OPENAI_AUDIO_TRANSCRIPTION_CONNECT_TIMEOUT', 30),
    ],
    'localWhisper' => [
        'pythonBinary' => env('LOCAL_WHISPER_PYTHON_BINARY', 'python3'),
        'scriptPath' => env('LOCAL_WHISPER_SCRIPT_PATH', base_path('scripts/transcribe_audio.py')),
        'backend' => env('LOCAL_WHISPER_BACKEND', 'whisper'),
        'model' => env('LOCAL_WHISPER_MODEL', 'base'),
        'device' => env('LOCAL_WHISPER_DEVICE', 'auto'),
        'computeType' => env('LOCAL_WHISPER_COMPUTE_TYPE', 'auto'),
        'beamSize' => (int) env('LOCAL_WHISPER_BEAM_SIZE', 5),
        'timeoutSeconds' => (int) env('LOCAL_WHISPER_TIMEOUT', 1200),
    ],
    'srt' => [
        'maxLineLength' => (int) env('SUBTITLES_SRT_MAX_LINE_LENGTH', 42),
    ],
];
