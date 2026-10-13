<?php

use App\Http\Middleware\ConferirCaptcha;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Em produção a API fica atrás do proxy da hospedagem. Confiar nele faz
        // o Laravel enxergar o IP de quem chamou, usado no limite de tentativas.
        $middleware->trustProxies(at: '*');

        $middleware->alias(['captcha' => ConferirCaptcha::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // O 404 padrão cita o nome interno do model; a API responde só o necessário.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Registro não encontrado.'], 404);
            }
        });
    })->create();
