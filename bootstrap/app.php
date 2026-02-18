<?php

use App\Http\Middleware\EnsureApiRequestsAcceptJson;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            EnsureApiRequestsAcceptJson::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e) {
            $previous = $e->getPrevious();

            if (! $previous instanceof ModelNotFoundException) {
                return null;
            }

            $modelName = class_basename($previous->getModel());

            return response()->json([
                'message' => "{$modelName} does not exist.",
            ], 404);
        });
    })->create();
