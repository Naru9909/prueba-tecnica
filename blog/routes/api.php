<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Bad\BadPostController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| PREGUNTA: ¿Qué diferencia hay entre rutas en api.php y web.php?
| ¿Qué middlewares se aplican automáticamente a las rutas API?
| ¿Qué es el middleware 'throttle' y por qué es importante en una API pública?
|
*/

// =========================================================================
// RUTAS PÚBLICAS (sin autenticación)
// =========================================================================

Route::prefix('v1')->name('api.v1.')->group(function () {

    // =========================================================================
    // AUTENTICACIÓN (Sanctum)
    // PREGUNTA: ¿Por qué estas rutas están FUERA del middleware 'auth:sanctum'?
    // ¿Qué pasaría si las pusieras dentro?
    // =========================================================================
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login',    [AuthController::class, 'login'])->name('login');

        // Las siguientes rutas SÍ requieren autenticación
        // PREGUNTA: ¿Por qué tiene sentido proteger /logout con auth:sanctum?
        // ¿Qué pasaría si no lo protegieras?
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout',     [AuthController::class, 'logout'])->name('logout');
            Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
            Route::get('/me',          [AuthController::class, 'me'])->name('me');
        });
    });

    // Posts públicos
    // PREGUNTA: ¿Qué hace apiResource() vs resource()?
    // ¿Qué rutas genera apiResource() y cuáles omite?
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

    // Posts por slug (Route Model Binding personalizado)
    // PREGUNTA: ¿Cómo se configura Route Model Binding para buscar por 'slug'?
    // ¿Dónde se define: en el modelo, en el RouteServiceProvider, o en la ruta?
    Route::get('/posts/slug/{slug}', function (string $slug, \App\Services\PostService $service) {
        $post = $service->getPostBySlug($slug);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }
        return new \App\Http\Resources\PostResource($post);
    });

    // Categorías
    Route::get('/categories', function () {
        return \App\Http\Resources\CategoryResource::collection(
            \App\Models\Category::active()->with('children')->root()->get()
        );
    });

    // Tags
    Route::get('/tags', function () {
        return \App\Http\Resources\TagResource::collection(
            \App\Models\Tag::withCount('posts')->orderByDesc('posts_count')->get()
        );
    });

    // =========================================================================
    // RUTAS AUTENTICADAS
    // PREGUNTA: ¿Qué hace el middleware 'auth:sanctum'?
    // ¿Cuál es la diferencia entre Sanctum, Passport y JWT?
    // =========================================================================
    Route::middleware('auth:sanctum')->group(function () {

        // Perfil del usuario autenticado
        Route::get('/me', function (Request $request) {
            return new \App\Http\Resources\UserResource($request->user());
        });

        // CRUD de posts (solo editores/admins — validado en StorePostRequest::authorize())
        Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
        Route::patch('/posts/{post}', [PostController::class, 'update'])->name('posts.update');
        Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::post('/posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');

        // Comentarios
        Route::post('/posts/{post}/comments', [\App\Http\Controllers\Api\CommentController::class, 'store']);
        Route::patch('/comments/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'update']);
        Route::delete('/comments/{comment}', [\App\Http\Controllers\Api\CommentController::class, 'destroy']);

        // Likes (toggle)
        Route::post('/posts/{post}/like', [\App\Http\Controllers\Api\LikeController::class, 'togglePost']);
        Route::post('/comments/{comment}/like', [\App\Http\Controllers\Api\LikeController::class, 'toggleComment']);

        // Bookmarks
        Route::post('/posts/{post}/bookmark', [\App\Http\Controllers\Api\BookmarkController::class, 'toggle']);
        Route::get('/bookmarks', [\App\Http\Controllers\Api\BookmarkController::class, 'index']);
    });

    // =========================================================================
    // RUTAS CON MALAS PRÁCTICAS (para la prueba técnica)
    // INSTRUCCIÓN: Estas rutas demuestran los problemas del BadPostController.
    // =========================================================================
    Route::prefix('bad')->name('bad.')->group(function () {
        Route::get('/posts', [BadPostController::class, 'index']);
        Route::get('/posts/search', [BadPostController::class, 'search']);
        Route::post('/posts', [BadPostController::class, 'store']);
        Route::get('/posts/{id}', [BadPostController::class, 'show']);
        Route::put('/posts/{id}', [BadPostController::class, 'update']);
        Route::delete('/posts/{id}', [BadPostController::class, 'destroy']);
        Route::get('/posts/stats', [BadPostController::class, 'stats']);
        Route::post('/posts/{postId}/comments', [BadPostController::class, 'storeComment']);
        Route::post('/posts/{id}/publish', [BadPostController::class, 'publishPost']);
        Route::get('/users/{id}', [BadPostController::class, 'getUser']);
    });
});
