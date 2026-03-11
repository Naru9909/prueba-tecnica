<?php

namespace App\Providers;

use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Eloquent\EloquentCommentRepository;
use App\Repositories\Eloquent\EloquentPostRepository;
use Illuminate\Support\ServiceProvider;

/**
 * PREGUNTA: ¿Por qué registramos los bindings en un Service Provider y no
 * directamente en el constructor del controller?
 * ¿Qué es el Service Container de Laravel y cómo funciona?
 * ¿Cuál es la diferencia entre bind() y singleton() en el container?
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * PREGUNTA: ¿Por qué se usa register() para bindings y boot() para
     * otras configuraciones? ¿Qué diferencia hay en el ciclo de vida de la aplicación?
     */
    public function register(): void
    {
        // Binding de Interfaces a Implementaciones concretas
        // Esto permite cambiar la implementación (ej: para testing) sin tocar el código
        $this->app->bind(
            PostRepositoryInterface::class,
            EloquentPostRepository::class
        );

        $this->app->bind(
            CommentRepositoryInterface::class,
            EloquentCommentRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
