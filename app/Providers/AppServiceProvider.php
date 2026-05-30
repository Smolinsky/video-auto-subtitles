<?php

namespace App\Providers;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\VideoRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\VideoRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(VideoRepositoryInterface::class, VideoRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DB::prohibitDestructiveCommands(! $this->usesInMemorySqliteDatabase());
    }

    private function usesInMemorySqliteDatabase(): bool
    {
        $connection = config('database.default');

        return config("database.connections.{$connection}.driver") === 'sqlite'
            && config("database.connections.{$connection}.database") === ':memory:';
    }
}
