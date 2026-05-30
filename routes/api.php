<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\VideoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:api')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::prefix('videos')->group(function (): void {
            Route::get('/', [VideoController::class, 'index']);
            Route::post('/', [VideoController::class, 'store']);
            Route::get('{uuid}/transcript', [VideoController::class, 'transcript']);
            Route::get('{uuid}/srt', [VideoController::class, 'srt']);
            Route::post('{uuid}/retry', [VideoController::class, 'retry']);
            Route::get('{uuid}', [VideoController::class, 'show']);
        });
    });
});
