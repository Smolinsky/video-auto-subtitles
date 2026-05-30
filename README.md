# Reels Auto Subtitles API

Laravel API for uploading short-form videos, extracting audio with FFmpeg, transcribing speech, and preparing subtitle assets for Short Video.

## Current MVP scope

- bearer-token auth with Laravel Passport
- upload video through API
- store video processing records in PostgreSQL
- queue background processing jobs
- extract audio from video with FFmpeg
- transcribe extracted audio with OpenAI Whisper API or a local Python Whisper runner
- generate and download `.srt` subtitles
- poll processing status

## Stack

- PHP 8.3
- Laravel 13
- PostgreSQL
- Laravel Queue
- FFmpeg
- OpenAI Whisper API or local Python Whisper
- Laravel Passport

## API endpoints

### Auth

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`

`register` / `login` are public. `logout` requires `Authorization: Bearer <token>`.

### Videos

- `GET /api/v1/videos`
- `POST /api/v1/videos`
- `GET /api/v1/videos/{uuid}`
- `GET /api/v1/videos/{uuid}/transcript`
- `GET /api/v1/videos/{uuid}/srt`
- `POST /api/v1/videos/{uuid}/retry`

All video endpoints require `Authorization: Bearer <token>`.

## Processing flow

1. client uploads a video
2. API stores the source file and creates a `videos` record
3. queue dispatches `ExtractAudioFromVideoJob`
4. FFmpeg extracts mono 16k audio
5. queue dispatches `TranscribeVideoJob`
6. transcription runs through OpenAI first or local Python fallback
7. API stores transcript segments and generates `captions.srt`
8. client polls the status endpoint until `transcribed` or `failed`

## Example upload

```bash
curl --request POST \
  --url http://localhost/api/v1/videos \
  --header "Authorization: Bearer <token>" \
  --header "Accept: application/json" \
  --form "video=@/absolute/path/to/reel.mp4"
```

## Important env vars

- `SUBTITLES_STORAGE_DISK`
- `SUBTITLES_QUEUE`
- `SUBTITLES_MAX_UPLOAD_KB`
- `FFMPEG_BINARY`
- `FFMPEG_AUDIO_CODEC`
- `FFMPEG_AUDIO_EXTENSION`
- `FFMPEG_AUDIO_SAMPLE_RATE`
- `FFMPEG_AUDIO_CHANNELS`
- `SUBTITLES_TRANSCRIPTION_DRIVER`
- `SUBTITLES_TRANSCRIPTION_PREFERRED_DRIVER`
- `SUBTITLES_TRANSCRIPTION_FALLBACK_DRIVERS`
- `OPENAI_API_KEY`
- `LOCAL_WHISPER_BACKEND`
- `LOCAL_WHISPER_MODEL`

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan passport:install
composer serve:api
php artisan queue:work --queue=media
```

## Upload limits

Video uploads are validated up to `SUBTITLES_MAX_UPLOAD_KB` (100 MB by default), so PHP must allow a slightly larger multipart request as well.

- `public/.user.ini` sets `upload_max_filesize=100M` and `post_max_size=120M` for Apache / PHP-FPM style setups.
- `composer serve:api` starts the built-in PHP server with the same limits for local Postman testing.
- `composer dev` now uses the same upload limits automatically.

## Local Whisper fallback

The repository includes `scripts/transcribe_audio.py` for local transcription. Install one of these Python packages in your environment:

- `pip install openai-whisper`
- or `pip install faster-whisper`

The script accepts the extracted audio path and returns normalized JSON that the Laravel job stores in PostgreSQL.

## Tests

```bash
php artisan test
```

`phpunit.xml` runs tests against in-memory SQLite (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), so your main PostgreSQL database is not modified.
