<?php

namespace App\Services;

use App\DTOs\CreatePostDTO;
use App\DTOs\PostFilterDTO;
use App\DTOs\UpdatePostDTO;
use App\Models\Post;
use App\Models\PostRevision;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PREGUNTA: ¿Por qué existe una capa de Service entre el Controller y el Repository?
 * ¿Qué tipo de lógica va en el Service vs en el Repository vs en el Controller?
 * Principio: "Thin Controllers, Fat Services, Smart Repositories"
 */
class PostService
{
    /**
     * PREGUNTA: ¿Por qué inyectamos la dependencia por el constructor
     * en lugar de instanciarla con 'new EloquentPostRepository()'?
     * ¿Qué es la Dependency Injection y por qué es importante?
     */
    public function __construct(
        private readonly PostRepositoryInterface $postRepository
    ) {}

    // =========================================================================
    // LECTURA
    // =========================================================================

    public function getPaginatedPosts(PostFilterDTO $filters): LengthAwarePaginator
    {
        return $this->postRepository->paginate($filters);
    }

    /**
     * PREGUNTA: Este método usa caché. ¿Por qué la clave de caché incluye el slug?
     * ¿Qué problema tendría si usáramos solo 'post_detail' como clave?
     *
     * PREGUNTA: ¿Cuándo debería invalidarse esta caché?
     * ¿Qué estrategia de invalidación de caché usarías?
     */
    public function getPostBySlug(string $slug): ?Post
    {
        return Cache::remember("post_detail_{$slug}", 3600, function () use ($slug) {
            return $this->postRepository->findBySlug($slug);
        });
    }

    public function getFeaturedPosts(int $limit = 6): Collection
    {
        // PREGUNTA: ¿Es adecuado cachear esto por 1 hora? ¿Qué pasa si publican
        // un post featured y tarda 1h en aparecer? ¿Cómo manejarías esto?
        return Cache::remember('featured_posts', 3600, function () use ($limit) {
            return $this->postRepository->findFeatured($limit);
        });
    }

    public function getPopularPosts(int $days = 7, int $limit = 10): Collection
    {
        return Cache::remember("popular_posts_{$days}d", 1800, function () use ($days, $limit) {
            return $this->postRepository->findPopular($days, $limit);
        });
    }

    public function getRelatedPosts(Post $post, int $limit = 5): Collection
    {
        return $this->postRepository->findRelated($post, $limit);
    }

    // =========================================================================
    // ESCRITURA
    // =========================================================================

    /**
     * PREGUNTA: ¿Qué hace Cache::forget() aquí y por qué es necesario?
     * ¿Qué estrategia es esto? (Cache-Aside Pattern)
     */
    public function createPost(CreatePostDTO $dto): Post
    {
        $post = $this->postRepository->create($dto);

        // Invalidar caches relevantes
        Cache::forget('featured_posts');
        Cache::forget('popular_posts_7d');

        Log::info('Post created', ['post_id' => $post->id, 'author' => $dto->userId]);

        return $post;
    }

    public function updatePost(Post $post, UpdatePostDTO $dto): Post
    {
        // Guardar revisión antes de actualizar
        $this->saveRevision($post);

        $updated = $this->postRepository->update($post, $dto);

        // Invalidar caché del post
        Cache::forget("post_detail_{$post->slug}");

        // Si cambió el slug (título), también el nuevo
        if ($dto->title && $updated->slug !== $post->slug) {
            Cache::forget("post_detail_{$updated->slug}");
        }

        Cache::forget('featured_posts');

        return $updated;
    }

    public function deletePost(Post $post): bool
    {
        Cache::forget("post_detail_{$post->slug}");
        Cache::forget('featured_posts');

        return $this->postRepository->delete($post);
    }

    public function trackView(int $postId): void
    {
        // PREGUNTA: ¿Qué problema tiene llamar a incrementViews() en cada
        // request de un post popular? ¿Cómo mejorarías esto?
        // (pista: Redis, Queue, Batching)
        $this->postRepository->incrementViews($postId);
    }

    // =========================================================================
    // LÓGICA DE NEGOCIO
    // =========================================================================

    /**
     * PREGUNTA: ¿Qué hace este método? ¿Por qué se guarda una revisión ANTES
     * de actualizar el post? ¿Qué principio de integridad de datos aplica?
     */
    private function saveRevision(Post $post): void
    {
        $lastRevision = $post->revisions()->latest()->first();
        $nextNumber   = $lastRevision ? $lastRevision->revision_number + 1 : 1;

        PostRevision::create([
            'post_id'         => $post->id,
            'user_id'         => $post->user_id,
            'title'           => $post->title,
            'content'         => $post->content,
            'excerpt'         => $post->excerpt,
            'revision_number' => $nextNumber,
        ]);
    }

    /**
     * Publicar un post (cambiar estado a 'published').
     *
     * PREGUNTA: ¿Por qué este tipo de operación (cambio de estado de negocio)
     * debería estar en el Service y no directamente en el Controller?
     */
    public function publishPost(Post $post, ?string $publishedAt = null): Post
    {
        if ($post->status === 'published') {
            throw new \LogicException("El post ya está publicado.");
        }

        $dto = UpdatePostDTO::fromRequest([
            'status'       => 'published',
            'published_at' => $publishedAt ?? now()->toDateTimeString(),
        ]);

        return $this->updatePost($post, $dto);
    }

    public function archivePost(Post $post): Post
    {
        $dto = UpdatePostDTO::fromRequest(['status' => 'archived']);
        return $this->updatePost($post, $dto);
    }
}
