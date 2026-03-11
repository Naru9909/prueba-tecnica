<?php

namespace Tests\Unit;

use App\DTOs\CreatePostDTO;
use App\DTOs\PostFilterDTO;
use App\DTOs\UpdatePostDTO;
use App\Models\Post;
use Tests\Fakes\InMemoryPostRepository;
use Tests\TestCase;

/**
 * PREGUNTA: ¿Por qué este test NO extiende RefreshDatabase
 * ni necesita una conexión a base de datos real?
 * ¿Qué ventaja tiene esto en términos de velocidad y aislamiento?
 *
 * PREGUNTA: ¿Qué es un "Unit Test" vs un "Feature Test" en Laravel?
 * ¿Qué debería testear cada uno?
 *
 * PREGUNTA: ¿Qué principio de testing aplica aquí?
 * (pista: "Don't test the framework, test YOUR code")
 */
class PostRepositoryTest extends TestCase
{
    private InMemoryPostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        /**
         * PREGUNTA: ¿Qué es setUp() y cuándo se llama?
         * ¿Qué diferencia hay entre setUp() y setUpBeforeClass()?
         */
        $this->repository = new InMemoryPostRepository();
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    /**
     * PREGUNTA: ¿Qué convención de nombres se usa para los test methods?
     * ¿Por qué es importante que el nombre sea descriptivo?
     * ¿Qué diferencia hay entre el prefijo 'test_' y la anotación @test?
     */
    public function test_create_stores_a_post_and_returns_it(): void
    {
        $dto = $this->makeCreateDTO(['title' => 'Mi primer post']);

        $post = $this->repository->create($dto);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals(1, $post->id);
        $this->assertEquals('Mi primer post', $post->title);
        $this->assertEquals('mi-primer-post', $post->slug);
    }

    public function test_create_increments_id_on_each_call(): void
    {
        $this->repository->create($this->makeCreateDTO(['title' => 'Post 1']));
        $this->repository->create($this->makeCreateDTO(['title' => 'Post 2']));
        $post3 = $this->repository->create($this->makeCreateDTO(['title' => 'Post 3']));

        $this->assertEquals(3, $post3->id);
        $this->assertEquals(3, $this->repository->count());
    }

    // =========================================================================
    // FIND BY ID / SLUG
    // =========================================================================

    public function test_find_by_id_returns_correct_post(): void
    {
        $this->repository->create($this->makeCreateDTO(['title' => 'Post A']));
        $created = $this->repository->create($this->makeCreateDTO(['title' => 'Post B']));

        $found = $this->repository->findById($created->id);

        $this->assertNotNull($found);
        $this->assertEquals('Post B', $found->title);
    }

    public function test_find_by_id_returns_null_for_nonexistent_id(): void
    {
        $result = $this->repository->findById(999);

        /**
         * PREGUNTA: ¿Por qué es importante testear el caso "not found"
         * además del caso "found"?
         */
        $this->assertNull($result);
    }

    public function test_find_by_slug_returns_correct_post(): void
    {
        $this->repository->create($this->makeCreateDTO(['title' => 'Laravel Tips']));

        $found = $this->repository->findBySlug('laravel-tips');

        $this->assertNotNull($found);
        $this->assertEquals('Laravel Tips', $found->title);
    }

    public function test_find_by_slug_returns_null_when_not_found(): void
    {
        $result = $this->repository->findBySlug('no-existe');

        $this->assertNull($result);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_update_modifies_the_post(): void
    {
        $post = $this->repository->create(
            $this->makeCreateDTO(['title' => 'Título original'])
        );

        $dto     = UpdatePostDTO::fromRequest(['title' => 'Título actualizado']);
        $updated = $this->repository->update($post, $dto);

        $this->assertEquals('Título actualizado', $updated->title);
        // Verificar que el repositorio también se actualizó
        $this->assertEquals('Título actualizado', $this->repository->findById($post->id)->title);
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function test_delete_removes_post_from_repository(): void
    {
        $post = $this->repository->create($this->makeCreateDTO(['title' => 'Post a borrar']));

        $this->assertEquals(1, $this->repository->count());

        $result = $this->repository->delete($post);

        /**
         * PREGUNTA: ¿Por qué verificamos tanto el valor retornado por delete()
         * como el estado interno del repositorio?
         * ¿Qué son los "side effects" en testing y por qué son importantes?
         */
        $this->assertTrue($result);
        $this->assertEquals(0, $this->repository->count());
        $this->assertNull($this->repository->findById($post->id));
    }

    // =========================================================================
    // VIEWS
    // =========================================================================

    public function test_increment_views_increases_count(): void
    {
        $post = $this->repository->create($this->makeCreateDTO(['title' => 'Post popular']));
        $post->views_count = 10;

        $this->repository->incrementViews($post->id);

        $this->assertEquals(11, $this->repository->findById($post->id)->views_count);
    }

    // =========================================================================
    // PAGINATE
    // =========================================================================

    public function test_paginate_returns_only_published_posts(): void
    {
        // Crear posts con distintos estados
        $published = $this->repository->create($this->makeCreateDTO(['title' => 'Published']));
        $published->status = 'published';

        $draft = $this->repository->create($this->makeCreateDTO(['title' => 'Draft']));
        $draft->status = 'draft';

        $filters = PostFilterDTO::fromRequest([]);
        $result  = $this->repository->paginate($filters);

        /**
         * PREGUNTA: ¿Qué tipo retorna paginate()? ¿Qué métodos tiene LengthAwarePaginator?
         * ¿Cuándo usarías simplePaginate() vs paginate() vs cursorPaginate()?
         */
        $this->assertEquals(1, $result->total());
    }

    // =========================================================================
    // FIND FEATURED / POPULAR
    // =========================================================================

    public function test_find_featured_returns_only_featured_published_posts(): void
    {
        $featured = $this->repository->create($this->makeCreateDTO(['title' => 'Featured']));
        $featured->status     = 'published';
        $featured->is_featured = true;

        $notFeatured = $this->repository->create($this->makeCreateDTO(['title' => 'Normal']));
        $notFeatured->status     = 'published';
        $notFeatured->is_featured = false;

        $result = $this->repository->findFeatured();

        $this->assertCount(1, $result);
        $this->assertEquals('Featured', $result->first()->title);
    }

    public function test_find_popular_sorts_by_views_descending(): void
    {
        $postA = $this->repository->create($this->makeCreateDTO(['title' => 'Post A']));
        $postA->status      = 'published';
        $postA->views_count = 100;

        $postB = $this->repository->create($this->makeCreateDTO(['title' => 'Post B']));
        $postB->status      = 'published';
        $postB->views_count = 500;

        $postC = $this->repository->create($this->makeCreateDTO(['title' => 'Post C']));
        $postC->status      = 'published';
        $postC->views_count = 250;

        $result = $this->repository->findPopular();

        $this->assertEquals('Post B', $result->first()->title);
        $this->assertEquals(500, $result->first()->views_count);
    }

    // =========================================================================
    // DEMOSTRACIÓN DEL VALOR DEL REPOSITORY PATTERN
    // =========================================================================

    /**
     * PREGUNTA: Este test demuestra la principal ventaja del Repository Pattern.
     * ¿Qué pasaría si PostService usara Eloquent directamente (sin repositorio)?
     * ¿Podrías escribir este test sin tocar la base de datos?
     *
     * RESPUESTA ESPERADA: NO. Si el Service tuviera lógica Eloquent directamente,
     * cada test requeriría una base de datos real (o SQLite en memoria),
     * haciendo los tests mucho más lentos y frágiles.
     *
     * El Repository Pattern permite inyectar cualquier implementación que
     * cumpla la interfaz, incluyendo esta implementación en memoria.
     */
    public function test_repository_pattern_allows_testing_without_database(): void
    {
        // Crear 5 posts directamente en memoria, sin SQL
        for ($i = 1; $i <= 5; $i++) {
            $post = $this->repository->create(
                $this->makeCreateDTO(['title' => "Post número {$i}"])
            );
            $post->status = 'published';
        }

        $this->assertEquals(5, $this->repository->count());

        // El repositorio funciona correctamente sin ninguna conexión a BD
        $found = $this->repository->findBySlug('post-numero-3');
        $this->assertNotNull($found);
        $this->assertEquals('Post número 3', $found->title);
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    private function makeCreateDTO(array $overrides = []): CreatePostDTO
    {
        return CreatePostDTO::fromRequest(array_merge([
            'user_id'     => 1,
            'category_id' => 1,
            'title'       => 'Test Post',
            'content'     => 'Contenido de prueba para el post.',
            'status'      => 'draft',
        ], $overrides));
    }
}
