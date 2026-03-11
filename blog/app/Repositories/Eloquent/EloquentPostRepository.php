<?php

namespace App\Repositories\Eloquent;

use App\DTOs\CreatePostDTO;
use App\DTOs\PostFilterDTO;
use App\DTOs\UpdatePostDTO;
use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PREGUNTA: Este repositorio implementa PostRepositoryInterface.
 * ¿Cómo se "registra" esta implementación en el Service Container de Laravel
 * para que cuando alguien pida PostRepositoryInterface, reciba este objeto?
 * (pista: AppServiceProvider o un RepositoryServiceProvider)
 */
class EloquentPostRepository implements PostRepositoryInterface
{
    public function __construct(
        private readonly Post $model
    ) {}

    /**
     * PREGUNTA: Analiza este método. ¿Cuántas queries se generan con estos
     * eager loads? ¿Hay algún eager load innecesario para un listado?
     * ¿Cuál sería el impacto de quitar 'content' del select()?
     */
    public function paginate(PostFilterDTO $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['author', 'category', 'tags'])
            ->where('status', $filters->status);

        if ($filters->categoryId) {
            $query->where('category_id', $filters->categoryId);
        }

        if ($filters->tagId) {
            // PREGUNTA: ¿Cuál es la diferencia entre whereHas y join para filtrar por relación?
            // ¿Cuál genera queries más eficientes y en qué escenarios?
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $filters->tagId));
        }

        if ($filters->authorId) {
            $query->where('user_id', $filters->authorId);
        }

        if ($filters->search) {
            // PREGUNTA: Esta búsqueda con LIKE tiene un problema de performance severo.
            // ¿Cuál es? ¿Qué solución usarías para un sistema de búsqueda de texto real?
            // (pista: FULLTEXT index, Meilisearch, Elasticsearch, Typesense)
            $search = $filters->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('excerpt', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        return $query
            ->orderBy($filters->sortBy, $filters->sortDir)
            ->paginate($filters->perPage);
    }

    public function findById(int $id): ?Post
    {
        // PREGUNTA: ¿Cuál es la diferencia entre find(), findOrFail() y firstOrFail()?
        // ¿Cuándo retorna null cada uno vs cuándo lanza una excepción?
        return $this->model->with(['author', 'category', 'tags', 'comments'])->find($id);
    }

    public function findBySlug(string $slug): ?Post
    {
        return $this->model
            ->with(['author', 'category', 'tags'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * PREGUNTA: ¿Por qué se usa una transacción de base de datos aquí?
     * ¿Qué problema resuelve DB::transaction() en este caso?
     * ¿Qué sucede si syncTags lanza una excepción?
     */
    public function create(CreatePostDTO $dto): Post
    {
        return DB::transaction(function () use ($dto) {
            $post = $this->model->create($dto->toArray());

            if ($dto->tagIds !== null) {
                $post->tags()->sync($dto->tagIds);
            }

            return $post->load(['author', 'category', 'tags']);
        });
    }

    public function update(Post $post, UpdatePostDTO $dto): Post
    {
        return DB::transaction(function () use ($post, $dto) {
            $post->update($dto->toArray());

            if ($dto->tagIds !== null) {
                // PREGUNTA: ¿Cuál es la diferencia entre sync(), attach() y syncWithoutDetaching()?
                // ¿Cuándo usarías cada uno en el contexto de los tags de un post?
                $post->tags()->sync($dto->tagIds);
            }

            return $post->fresh(['author', 'category', 'tags']);
        });
    }

    public function delete(Post $post): bool
    {
        // SoftDelete: no borra físicamente
        return (bool) $post->delete();
    }

    /**
     * PREGUNTA: Este método usa un UPDATE directo con DB::table() en lugar de
     * cargar el modelo con Eloquent y llamar $post->increment().
     * ¿Cuál es la diferencia? ¿Cuándo es preferible cada enfoque?
     * ¿Cuándo podrías usar Redis para los contadores de vistas?
     */
    public function incrementViews(int $postId): void
    {
        DB::table('posts')
            ->where('id', $postId)
            ->increment('views_count');
    }

    public function findRelated(Post $post, int $limit = 5): Collection
    {
        // PREGUNTA: Esta query tiene un posible problema de performance.
        // ¿Cuál es? ¿Cómo mejorarías la selección de posts relacionados?
        $tagIds = $post->tags->pluck('id');

        return $this->model
            ->with(['author', 'category'])
            ->published()
            ->where('id', '!=', $post->id)
            ->where(function ($q) use ($post, $tagIds) {
                $q->where('category_id', $post->category_id)
                  ->orWhereHas('tags', fn ($tq) => $tq->whereIn('tags.id', $tagIds));
            })
            ->limit($limit)
            ->get();
    }

    public function findFeatured(int $limit = 6): Collection
    {
        return $this->model
            ->with(['author', 'category'])
            ->published()
            ->featured()
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function findPopular(int $days = 7, int $limit = 10): Collection
    {
        return $this->model
            ->with(['author', 'category'])
            ->published()
            ->where('published_at', '>=', now()->subDays($days))
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();
    }
}
