<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $this->forceSafeTestEnvironment();

        $app = parent::createApplication();

        $this->guardAgainstPersistentTestDatabase();

        return $app;
    }

    protected function beforeRefreshingDatabase()
    {
        $this->guardAgainstPersistentTestDatabase();
    }

    private function forceSafeTestEnvironment(): void
    {
        foreach ([
            'APP_ENV' => 'testing',
            'APP_CONFIG_CACHE' => '/tmp/video-auto-subtitles-phpunit-config.php',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
        ] as $key => $value) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    private function guardAgainstPersistentTestDatabase(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('Refusing to run database tests outside the testing environment.');
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $database = config("database.connections.{$connection}.database");

        if ($driver === 'sqlite' && $database === ':memory:') {
            return;
        }

        throw new RuntimeException(sprintf(
            'Refusing to run database tests against persistent database [%s:%s]. Tests must use sqlite :memory: to prevent wiping local data.',
            $driver ?? 'unknown',
            is_scalar($database) ? (string) $database : 'unknown',
        ));
    }
}
