<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\PrivacyHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (!$request->is('api/*')) return null;
            $status = $e instanceof \Illuminate\Validation\ValidationException ? 422 : ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500);
            $body = ['message'=>$status >= 500 ? 'Service is unavailable. Please try again.' : $e->getMessage(), 'code'=>[401=>'unauthenticated',403=>'forbidden',404=>'not_found',409=>'conflict',419=>'csrf_mismatch',422=>'invalid_request',429=>'rate_limited'][$status] ?? 'service_unavailable'];
            if ($e instanceof \Illuminate\Validation\ValidationException) $body['errors']=$e->errors();
            return response()->json($body,$status)->header('Cache-Control','no-store');
        });
    })->create();
