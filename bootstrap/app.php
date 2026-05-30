<?php

use App\Support\UploadSizeLimit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => UploadSizeLimit::postTooLargeMessage(),
                    'limits' => [
                        'phpUploadMax' => UploadSizeLimit::phpUploadMax(),
                        'phpPostMax' => UploadSizeLimit::phpPostMax(),
                        'appUploadMaxMb' => UploadSizeLimit::appUploadMaxMegabytes(),
                        'recommendedUploadMax' => UploadSizeLimit::recommendedUploadMax(),
                        'recommendedPostMax' => UploadSizeLimit::recommendedPostMax(),
                    ],
                ], $exception->getStatusCode());
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'HTTP error.',
                ], $exception->getStatusCode());
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $exception->errors(),
                ], $exception->status);
            }
        });
    })->create();
