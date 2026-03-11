<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/**
 * PREGUNTA: ¿Qué es una Policy en Laravel y cómo se diferencia de un Middleware?
 * ¿Cómo registra Laravel automáticamente las Policies? (convención de nombres)
 * ¿Qué pasa si un método de la Policy retorna null vs false?
 *
 * PREGUNTA: ¿Qué es el método 'before()' en una Policy y cuándo usarlo?
 * ¿Cómo lo implementarías para que los admins puedan hacer todo?
 */
class PostPolicy
{
    /**
     * Permite que los admins hagan cualquier acción sin pasar por los otros métodos.
     *
     * PREGUNTA: ¿Qué diferencia hay entre retornar 'true' aquí
     * vs retornar null? ¿Cuándo querrías retornar null?
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null; // Continúa evaluando el método correspondiente
    }

    /**
     * PREGUNTA: ¿Por qué 'viewAny' no recibe un $post como argumento?
     * ¿Qué diferencia hay entre una "model policy" y una "non-model policy action"?
     */
    public function viewAny(?User $user): bool
    {
        // Posts publicados son visibles para todos (incluso no autenticados)
        return true;
    }

    /**
     * PREGUNTA: El parámetro $user es nullable aquí (con ?).
     * ¿Por qué? ¿Qué significa que sea nullable en el contexto de una Policy?
     */
    public function view(?User $user, Post $post): bool
    {
        if ($post->status === 'published') {
            return true;
        }

        // Drafts y archivados solo los ve su autor o editores
        return $user?->id === $post->user_id || $user?->isEditor() === true;
    }

    /**
     * Solo editores y admins pueden crear posts.
     *
     * PREGUNTA: ¿Dónde se llama este método — en el Controller, en el FormRequest,
     * o en ambos? ¿Cuál es la diferencia de hacerlo en cada lugar?
     */
    public function create(User $user): bool
    {
        return $user->isEditor();
    }

    /**
     * Solo el autor del post o un admin puede editarlo.
     *
     * PREGUNTA: ¿Cómo se llama esta Policy desde un Controller?
     * Escribe el código: $this->authorize(_____, $post)
     *
     * PREGUNTA: ¿Qué HTTP status retorna Laravel automáticamente
     * si la Policy retorna false? ¿403 o 401? ¿Cuándo retorna cada uno?
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isEditor();
    }

    /**
     * Solo el autor o un admin puede eliminar un post.
     *
     * PREGUNTA: ¿Qué diferencia hay entre soft delete y hard delete?
     * ¿Cómo afecta SoftDeletes a esta Policy? ¿Necesitarías un método 'restore()'?
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }

    /**
     * Solo editores o el autor propio pueden publicar.
     *
     * PREGUNTA: Este método 'publish' no es un método estándar de Policy
     * (los estándar son: viewAny, view, create, update, delete, restore, forceDelete).
     * ¿Cómo registras y llamas un método custom de Policy?
     */
    public function publish(User $user, Post $post): bool
    {
        return $user->isEditor() || $user->id === $post->user_id;
    }

    /**
     * PREGUNTA: ¿Cuándo usarías 'restore' y 'forceDelete'?
     * ¿Qué diferencia hay entre delete() y forceDelete() en el contexto de SoftDeletes?
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->isAdmin();
    }
}
