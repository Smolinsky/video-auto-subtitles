<?php

namespace App\Support;

class UploadSizeLimit
{
    public static function phpUploadMax(): string
    {
        return self::normalizedIniSize((string) ini_get('upload_max_filesize'));
    }

    public static function phpPostMax(): string
    {
        return self::normalizedIniSize((string) ini_get('post_max_size'));
    }

    public static function appUploadMaxMegabytes(): int
    {
        return (int) ceil(((int) config('subtitles.maxUploadKb', 102400)) / 1024);
    }

    public static function recommendedUploadMax(): string
    {
        return self::appUploadMaxMegabytes().'M';
    }

    public static function recommendedPostMax(): string
    {
        return (self::appUploadMaxMegabytes() + 20).'M';
    }

    public static function postTooLargeMessage(): string
    {
        return sprintf(
            'Upload payload exceeds the PHP server limit (upload_max_filesize=%s, post_max_size=%s). This API accepts videos up to %d MB, so increase upload_max_filesize to at least %s and post_max_size to at least %s, then retry.',
            self::phpUploadMax(),
            self::phpPostMax(),
            self::appUploadMaxMegabytes(),
            self::recommendedUploadMax(),
            self::recommendedPostMax(),
        );
    }

    private static function normalizedIniSize(string $value): string
    {
        $normalized = trim($value);

        return $normalized !== '' ? strtoupper($normalized) : '0';
    }
}
