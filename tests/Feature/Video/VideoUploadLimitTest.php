<?php

namespace Tests\Feature\Video;

use App\Support\UploadSizeLimit;
use Tests\TestCase;

final class VideoUploadLimitTest extends TestCase
{
    public function test_post_too_large_returns_actionable_api_response(): void
    {
        $postMaxBytes = $this->iniSizeToBytes((string) ini_get('post_max_size'));

        if ($postMaxBytes === null) {
            self::markTestSkipped('post_max_size is unlimited for this PHP runtime.');
        }

        $response = $this->call(
            'POST',
            '/api/v1/videos',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'multipart/form-data; boundary=----codex',
                'CONTENT_LENGTH' => (string) ($postMaxBytes + 1),
            ],
        );

        $response
            ->assertStatus(413)
            ->assertJsonPath('message', UploadSizeLimit::postTooLargeMessage())
            ->assertJsonPath('limits.phpUploadMax', UploadSizeLimit::phpUploadMax())
            ->assertJsonPath('limits.phpPostMax', UploadSizeLimit::phpPostMax())
            ->assertJsonPath('limits.appUploadMaxMb', UploadSizeLimit::appUploadMaxMegabytes())
            ->assertJsonPath('limits.recommendedUploadMax', UploadSizeLimit::recommendedUploadMax())
            ->assertJsonPath('limits.recommendedPostMax', UploadSizeLimit::recommendedPostMax());
    }

    private function iniSizeToBytes(string $value): ?int
    {
        $normalized = trim($value);

        if ($normalized === '-1') {
            return null;
        }

        if ($normalized === '' || is_numeric($normalized)) {
            return (int) $normalized;
        }

        $unit = strtolower(substr($normalized, -1));
        $number = (float) substr($normalized, 0, -1);
        $multiplier = match ($unit) {
            'k' => 1024,
            'm' => 1024 * 1024,
            'g' => 1024 * 1024 * 1024,
            default => 1,
        };

        return (int) round($number * $multiplier);
    }
}
