# Postman

## Files

- `Reels Auto Subtitles API.postman_collection.json` - готова collection
- `Reels Auto Subtitles.local.postman_environment.json` - готове local environment

## Before Import

1. Підніміть Laravel API:

```bash
php artisan migrate
php artisan passport:install
composer serve:api
```

2. У другому терміналі запустіть чергу:

```bash
php artisan queue:work --queue=media
```

3. Переконайтесь, що встановлено `ffmpeg`.

4. Для транскрипції підготуйте один з режимів:

- OpenAI API:

```env
OPENAI_API_KEY=your_key_here
SUBTITLES_TRANSCRIPTION_DRIVER=auto
SUBTITLES_TRANSCRIPTION_PREFERRED_DRIVER=openai
SUBTITLES_TRANSCRIPTION_FALLBACK_DRIVERS=local
```

- Local Whisper fallback:

```bash
pip install openai-whisper
```

або

```bash
pip install faster-whisper
```

## Import Into Postman

1. У Postman натисніть `Import`.
2. Імпортуйте collection:

- `/Users/macbookpro16/text/video-auto-subtitles/postman/Reels Auto Subtitles API.postman_collection.json`

3. За бажанням імпортуйте environment:

- `/Users/macbookpro16/text/video-auto-subtitles/postman/Reels Auto Subtitles.local.postman_environment.json`

4. Якщо імпортували environment, виберіть його зверху в Postman.

## Variables To Fill

- `baseUrl`
  - за замовчуванням: `http://127.0.0.1:8000/api/v1`
- `userName`
  - приклад: `Jane Doe`
- `userEmail`
  - приклад: `jane@example.com`
- `userPassword`
  - мінімум 8 символів, приклад: `secret123`

## Request Order

1. `Auth / Register`
2. або `Auth / Login`
3. `Videos / Upload Video`
4. `Videos / Get Video By UUID`
5. `Videos / Get Transcript`
6. `Videos / Download SRT`

## What Is Filled Automatically

- після `Register` або `Login` collection збереже `token`
- після `Upload Video` collection збереже `videoUuid`

## Request Payloads

### Register

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secret123",
  "passwordConfirmation": "secret123"
}
```

### Login

```json
{
  "email": "jane@example.com",
  "password": "secret123"
}
```

### Upload Video

- body type: `form-data`
- key: `video`
- value: video file
- allowed extensions: `mp4`, `mov`, `avi`, `webm`, `mkv`

## Notes

- У `Upload Video` після імпорту треба вручну вибрати файл у полі `video`.
- Для `Download SRT` дочекайтесь, поки у `Get Video By UUID` буде `srtReady = true` або `status = transcribed`.
- Якщо транскрипція впала, у відповіді `Get Video By UUID` буде `status = failed` і `failureMessage`.
- Якщо бачите `The POST data is too large`, значить PHP запущений із занизькими `upload_max_filesize` / `post_max_size`; `composer serve:api` вже піднімає сервер із лімітами, сумісними з 100 MB upload.
