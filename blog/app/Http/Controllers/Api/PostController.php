<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreatePostDTO;
use App\DTOs\PostFilterDTO;
use App\DTOs\UpdatePostDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PREGUNTA: Compara este controller con BadPostController.
 * ¿Qué principios aplica este y cuáles viola el "Bad"?
 * ¿Qué hace el Route Model Binding en los métodos show/update/destroy?
 */
class PostController extends Controller
{
    /**
     * PREGUNTA: ¿Qué diferencia hay entre inyectar el Service aquí
     * vs resolverlo con app(PostService::class) dentro de cada método?
     */
    public function __construct(
        private readonly PostService $postService
    ) {}

    /**
     * GET /api/posts
     *
     * PREGUNTA: ¿Qué hace PostFilterDTO::fromRequest() aquí?
     * ¿Por qué es preferible a pasar $request directamente al service?
     */
    public function index(Request $request): PostCollection
    {
        $filters = PostFilterDTO::fromRequest($request->all());
        $posts   = $this->postService->getPaginatedPosts($filters);

        return new PostCollection($posts);
    }

    /**
     * POST /api/posts
     *
     * PREGUNTA: ¿Qué es un Form Request en Laravel?
     * ¿Dónde se ejecuta la validación — antes o después de entrar al método?
     * ¿Qué pasa si la validación falla? ¿Qué código HTTP retorna?
     */
    public function store(StorePostRequest $request): JsonResponse
    {
        $dto  = CreatePostDTO::fromRequest(
            array_merge($request->validated(), ['user_id' => $request->user()->id])
        );
        $post = $this->postService->createPost($dto);

        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/posts/{post}
     *
     * PREGUNTA: ¿Qué es el Route Model Binding implícito en Laravel?
     * ¿Cómo sabe Laravel que debe buscar por 'id' por defecto?
     * ¿Cómo lo cambiarías para que busque por 'slug' en vez de por id?
     */
    public function show(Post $post): PostResource
    {
        $this->postService->trackView($post->id);

        return new PostResource($post->load(['author', 'category', 'tags']));
    }

    /**
     * PATCH /api/posts/{post}
     *
     * PREGUNTA: ¿Qué hace $this->authorize() exactamente?
     * ¿Qué excepción lanza si el usuario no tiene permiso?
     * ¿Qué HTTP status devuelve Laravel en ese caso?
     */
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $this->authorize('update', $post);

        $dto     = UpdatePostDTO::fromRequest($request->validated());
        $updated = $this->postService->updatePost($post, $dto);

        return new PostResource($updated);
    }

    /**
     * DELETE /api/posts/{post}
     *
     * PREGUNTA: ¿Por qué aquí usamos 'delete' y no 'destroy' como nombre del método
     * en la Policy? ¿Es una coincidencia o una convención de Laravel?
     */
    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $this->postService->deletePost($post);

        return response()->json(null, 204);
    }

    /**
     * POST /api/posts/{post}/publish
     *
     * PREGUNTA: 'publish' es un método custom en PostPolicy (no es uno de los
     * métodos estándar). ¿Cómo sabe Laravel a qué Policy y método llamar
     * cuando escribimos $this->authorize('publish', $post)?
     */
    public function publish(Post $post): PostResource
    {
        $this->authorize('publish', $post);

        $post = $this->postService->publishPost($post);

        return new PostResource($post);
    }
}
