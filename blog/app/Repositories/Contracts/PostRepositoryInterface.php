<?php

namespace App\Repositories\Contracts;

use App\DTOs\CreatePostDTO;
use App\DTOs\PostFilterDTO;
use App\DTOs\UpdatePostDTO;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * PREGUNTA: ¿Por qué definimos una interfaz para el repositorio en lugar de
 * usar directamente la clase concreta?
 * ¿Qué principio SOLID aplica aquí? (pista: Dependency Inversion Principle)
 * ¿Cómo facilita esto el testing con mocks?
 */
interface PostRepositoryInterface
{
    /**
     * Obtener posts paginados con filtros aplicados.
     *
     * PREGUNTA: ¿Por qué retornamos LengthAwarePaginator aquí en lugar de Collection?
     * ¿Cuándo usarías cada uno?
     */
    public function paginate(PostFilterDTO $filters): LengthAwarePaginator;

    public function findById(int $id): ?Post;

    public function findBySlug(string $slug): ?Post;

    /**
     * PREGUNTA: ¿Por qué este método recibe un DTO en lugar de un array?
     * ¿Qué beneficio aporta en términos de type safety?
     */
    public function create(CreatePostDTO $dto): Post;

    public function update(Post $post, UpdatePostDTO $dto): Post;

    public function delete(Post $post): bool;

    public function incrementViews(int $postId): void;

    /**
     * Obtener posts relacionados por categoría o tags, excluyendo el post actual.
     */
    public function findRelated(Post $post, int $limit = 5): Collection;

    public function findFeatured(int $limit = 6): Collection;

    /**
     * PREGUNTA: ¿Por qué es importante que los métodos del repositorio
     * NO contengan lógica de negocio? ¿Dónde debería estar esa lógica?
     */
    public function findPopular(int $days = 7, int $limit = 10): Collection;
}
