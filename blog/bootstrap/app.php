<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        /*
        |--------------------------------------------------------------------------
        | Manejo de Excepciones para API JSON
        |--------------------------------------------------------------------------
        |
        | PREGUNTA: ¿Qué es el método render() en el manejo de excepciones de Laravel?
        | ¿Cuándo se llama render() vs report()?
        | ¿Qué diferencia hay entre manejar excepciones aquí vs en un try/catch
        | dentro de cada controller?
        |
        | PREGUNTA: ¿Por qué es importante retornar siempre JSON en una API
        | en lugar de dejar que Laravel retorne la página de error HTML por defecto?
        */

        /**
         * PREGUNTA: ¿Qué significa 401 Unauthorized vs 403 Forbidden?
         * ¿En qué situación se lanza AuthenticationException?
         * (Pista: ocurre cuando el usuario NO está autenticado, no cuando no tiene permisos)
         */
        $exceptions->render(function (AuthenticationException $e, $request): JsonResponse|null {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'No autenticado. Por favor, inicia sesión.',
                    'error'   => 'unauthenticated',
                ], 401);
            }
            return null;
        });

        /**
         * PREGUNTA: ¿Qué es ValidationException y cuándo se lanza?
         * ¿Qué diferencia hay entre los errores de validación (422)
         * y otros errores del cliente (400)?
         *
         * PREGUNTA: ¿Por qué retornamos los errores como un objeto plano
         * en lugar del formato Laravel por defecto?
         */
        $exceptions->render(function (ValidationException $e, $request): JsonResponse|null {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Los datos proporcionados no son válidos.',
                    'errors'  => $e->errors(),
                ], 422);
            }
            return null;
        });

        /**
         * PREGUNTA: ¿Cuándo se lanza ModelNotFoundException vs NotFoundHttpException?
         * ¿Qué hace Route Model Binding cuando el modelo no existe?
         * ¿Cuándo lanzaría NotFoundHttpException en lugar de ModelNotFoundException?
         */
        $exceptions->render(function (ModelNotFoundException $e, $request): JsonResponse|null {
            if ($request->is('api/*') || $request->expectsJson()) {
                $model = class_basename($e->getModel());

                return response()->json([
                    'message' => "{$model} no encontrado.",
                    'error'   => 'model_not_found',
                ], 404);
            }
            return null;
        });

        $exceptions->render(function (NotFoundHttpException $e, $request): JsonResponse|null {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'El recurso solicitado no existe.',
                    'error'   => 'not_found',
                ], 404);
            }
            return null;
        });

        /**
         * PREGUNTA: ¿Cuándo se lanza AccessDeniedHttpException?
         * ¿Está relacionada con las Policies? ¿Qué lanza $this->authorize() cuando falla?
         */
        $exceptions->render(function (AccessDeniedHttpException $e, $request): JsonResponse|null {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'No tienes permiso para realizar esta acción.',
                    'error'   => 'forbidden',
                ], 403);
            }
            return null;
        });

    })->create();
