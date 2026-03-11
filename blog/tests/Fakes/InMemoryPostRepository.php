<?php

namespace Tests\Fakes;

use App\DTOs\CreatePostDTO;
use App\DTOs\PostFilterDTO;
use App\DTOs\UpdatePostDTO;
use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;

/**
 * PREGUNTA: ¿Por qué es valioso tener una implementación "fake" (en memoria)
 * del repositorio para los tests?
 *
 * RESPUESTA ESPERADA: Permite testear la lógica de negocio (PostService)
 * sin tocar la base de datos real, haciendo los tests mucho más rápidos
 * y deterministas. Este es el principal beneficio del Repository Pattern.
 *
 * PREGUNTA EXTRA: ¿Qué diferencia hay entre un Fake, un Mock y un Stub?
 * ¿Cuándo usarías cada uno?
 */
class InMemoryPostRepository implements PostRepositoryInterface
{
    /** @var array<int, Post> */
    private array $posts = [];
    private int $nextId = 1;

    public function paginate(PostFilterDTO $filters): LengthAwarePaginator
    {
        $filtered = array_values(array_filter(
            $this->posts,
            fn (Post $p) => $p->status === 'published'
        ));

        $perPage = $filters->perPage ?? 15;
        $page    = request()->input('page', 1);
        $offset  = ($page - 1) * $perPage;
        $items   = array_slice($filtered, $offset, $perPage);

        return new ConcretePaginator(
            $items,
            count($filtered),
            $perPage,
            $page,
        );
    }

    public function findById(int $id): ?Post
    {
        return $this->posts[$id] ?? null;
    }

    public function findBySlug(string $slug): ?Post
    {
        foreach ($this->posts as $post) {
            if ($post->slug === $slug) {
                return $post;
            }
        }

        return null;
    }

    public function create(CreatePostDTO $dto): Post
    {
        $post               = new Post($dto->toArray());
        $post->id           = $this->nextId++;
        $post->slug         = \Illuminate\Support\Str::slug($dto->title);
        $this->posts[$post->id] = $post;

        return $post;
    }

    public function update(Post $post, UpdatePostDTO $dto): Post
    {
        $data = array_filter($dto->toArray(), fn ($v) => $v !== null);

        foreach ($data as $key => $value) {
            $post->{$key} = $value;
        }

        $this->posts[$post->id] = $post;

        return $post;
    }

    public function delete(Post $post): bool
    {
        unset($this->posts[$post->id]);

        return true;
    }

    public function incrementViews(int $postId): void
    {
        if (isset($this->posts[$postId])) {
            $this->posts[$postId]->views_count++;
        }
    }

    public function findRelated(Post $post, int $limit = 5): Collection
    {
        $related = array_filter(
            $this->posts,
            fn (Post $p) => $p->id !== $post->id
                         && $p->category_id === $post->category_id
                         && $p->status === 'published'
        );

        return new Collection(array_slice(array_values($related), 0, $limit));
    }

    public function findFeatured(int $limit = 6): Collection
    {
        $featured = array_filter(
            $this->posts,
            fn (Post $p) => $p->is_featured && $p->status === 'published'
        );

        return new Collection(array_slice(array_values($featured), 0, $limit));
    }

    public function findPopular(int $days = 7, int $limit = 10): Collection
    {
        $published = array_filter(
            $this->posts,
            fn (Post $p) => $p->status === 'published'
        );

        usort($published, fn (Post $a, Post $b) => $b->views_count <=> $a->views_count);

        return new Collection(array_slice($published, 0, $limit));
    }

    // =========================================================================
    // Helpers para tests
    // =========================================================================

    public function count(): int
    {
        return count($this->posts);
    }

    /** @return array<int, Post> */
    public function all(): array
    {
        return $this->posts;
    }
}
