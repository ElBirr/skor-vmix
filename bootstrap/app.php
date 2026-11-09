<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Upload melebihi post_max_size: PHP sudah membuang isi form, jadi beri pesan yang jelas.
        $exceptions->render(fn (PostTooLargeException $e, Request $request) => $request->expectsJson()
            ? response()->json(['message' => 'Ukuran upload terlalu besar.'], 413)
            : response()->view('errors.upload-too-large', ['limit' => ini_get('post_max_size')], 413));
    })->create();
