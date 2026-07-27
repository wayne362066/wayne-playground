<?php

use App\Core\Http\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception): bool => $request->is('api/*')
                || $request->expectsJson()
        );

        $exceptions->render(function (ValidationException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::validationError($exception->errors())
                : null;
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('找不到指定的 API', 404)
                : null;
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error('沒有權限執行此操作', 403)
                : null;
        });

        $exceptions->render(function (HttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $exception->getStatusCode();
            $message = $status === 419
                ? '頁面已過期，請重新操作'
                : '請求無法完成';

            return ApiResponse::error($message, $status);
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            report($exception);

            $context = config('app.debug')
                ? [
                    'exception' => $exception::class,
                    'detail' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]
                : [];

            return ApiResponse::error('系統發生錯誤', 500, $context);
        });
    })->create();
