<?php

namespace App\Http\Controllers\Api\Bad;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * ============================================================
 *  ARCHIVO DE EJEMPLO — MALAS PRÁCTICAS INTENCIONADAS
 *  Propósito: Prueba técnica para identificar y corregir
 *             problemas de diseño, performance y seguridad.
 * ============================================================
 *
 * INSTRUCCIÓN PARA EL CANDIDATO:
 * Revisa este controller método por método. Cada uno tiene al menos
 * UN problema (o varios). Identifícalos, explica por qué son un
 * problema y propón la solución correcta.
 *
 * Hay problemas de: N+1 queries, seguridad (SQLi, XSS, Mass Assignment),
 * violación de principios SOLID, lógica de negocio en el controller,
 * falta de validación, falta de autorización, y más.
 */
class BadPostController extends Controller
{
    // =========================================================
    // PROBLEMA 1: N+1 Query — La más clásica
    // =========================================================
    /**
     * PREGUNTA: Identifica el problema de performance en este método.
     * ¿Cuántas queries se ejecutan si hay 20 posts?
     * ¿Cómo lo corregirías? Escribe la versión correcta.
     */
    public function index(): JsonResponse
    {
        $posts = Post::all(); // BAD: carga toda la tabla sin paginación

        $result = [];
        foreach ($posts as $post) {
            $result[] = [
                'id'       => $post->id,
                'title'    => $post->title,
                // BAD: cada una de estas líneas genera una query adicional
                'author'   => $post->author->name,
                'category' => $post->category->name,
                'tags'     => $post->tags->pluck('name'),
                'comments' => $post->comments->count(),
            ];
        }

        return response()->json($result);
    }

    // =========================================================
    // PROBLEMA 2: SQL Injection
    // =========================================================
    /**
     * PREGUNTA: ¿Qué vulnerabilidad de seguridad crítica tiene este método?
     * Escribe un ejemplo de payload que la explotaría.
     * ¿Cómo lo corregirías usando los mecanismos de Laravel?
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->get('q');

        // BAD: interpolación directa en SQL raw — SQL Injection
        $posts = DB::select(
            "SELECT id, title, excerpt FROM posts WHERE title LIKE '%{$term}%'"
        );

        return response()->json($posts);
    }

    // =========================================================
    // PROBLEMA 3: Mass Assignment + Sin validación + Lógica en Controller
    // =========================================================
    /**
     * PREGUNTA: Encuentra al menos 4 problemas en este método.
     * Uno de ellos es una vulnerabilidad de seguridad.
     */
    public function store(Request $request): JsonResponse
    {
        // BAD 1: Sin validación — cualquier input pasa
        // BAD 2: Mass Assignment con todos los campos del request
        $post = Post::create($request->all());

        // BAD 3: Lógica de negocio mezclada con el controller
        $wordCount     = str_word_count(strip_tags($post->content ?? ''));
        $readingTime   = ceil($wordCount / 200);
        $post->reading_time = $readingTime;
        $post->slug         = Str::slug($post->title) . '-' . $post->id;
        $post->save();

        // BAD 4: No retorna código HTTP apropiado (debería ser 201)
        return response()->json($post);
    }

    // =========================================================
    // PROBLEMA 4: Autorización ausente
    // =========================================================
    /**
     * PREGUNTA: ¿Qué vulnerabilidad de seguridad tiene este método?
     * ¿Qué ocurre si usuario con id=5 manda DELETE /posts/1?
     * ¿Cómo implementarías la autorización correctamente en Laravel?
     * (pista: Policies, Gates, can() middleware)
     */
    public function destroy(int $id): JsonResponse
    {
        // BAD: Cualquier usuario autenticado puede borrar CUALQUIER post
        $post = Post::findOrFail($id);
        $post->delete();

        return response()->json(['message' => 'deleted']);
    }

    // =========================================================
    // PROBLEMA 5: Eager Loading innecesario + seleccionar todo
    // =========================================================
    /**
     * PREGUNTA: ¿Qué problema de performance tiene este método?
     * ¿Qué datos innecesarios se están trayendo de la base de datos?
     * ¿Cuántas queries genera el eager load de 'revisions'?
     */
    public function show(int $id): JsonResponse
    {
        // BAD: trae TODO incluyendo content largo, revisiones, etc.
        $post = Post::with([
            'author',
            'category',
            'tags',
            'comments.user',
            'revisions', // BAD: una revisión completa puede ser enorme; rara vez se necesita en show
            'comments.replies.user',
            'comments.likes',
        ])->findOrFail($id);

        // BAD: retorna la entidad completa con todos los campos incluyendo meta internos
        return response()->json($post);
    }

    // =========================================================
    // PROBLEMA 6: XSS + sin sanitizar output
    // =========================================================
    /**
     * PREGUNTA: ¿Qué vulnerabilidad introduce almacenar y retornar el
     * campo 'body' sin sanitizar? ¿Dónde debería sanitizarse?
     * ¿Cuál es la diferencia entre escapar output y sanitizar input?
     */
    public function storeComment(Request $request, int $postId): JsonResponse
    {
        // BAD: Sin validación, sin autorización, sin sanitización
        $comment = Comment::create([
            'post_id' => $postId,
            'user_id' => $request->user()->id,
            'body'    => $request->get('body'), // body puede contener <script>alert('xss')</script>
        ]);

        return response()->json($comment);
    }

    // =========================================================
    // PROBLEMA 7: Transacción faltante + inconsistencia de datos
    // =========================================================
    /**
     * PREGUNTA: ¿Qué pasa si la segunda operación (sincronizar tags) falla?
     * ¿En qué estado queda la base de datos? ¿Cómo lo solucionarías?
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        // BAD: Sin transacción — si syncTags falla, el post quedó parcialmente actualizado
        $post->update($request->only(['title', 'content', 'category_id', 'status']));

        if ($request->has('tags')) {
            // Imagina que aquí hay un bug o falla de red
            $post->tags()->sync($request->tags);
        }

        return response()->json($post);
    }

    // =========================================================
    // PROBLEMA 8: Cálculo N+1 en estadísticas
    // =========================================================
    /**
     * PREGUNTA: Analiza este método de stats.
     * Si hay 100 categorías, ¿cuántas queries se ejecutan?
     * ¿Cómo lo reescribirías para que use 1-3 queries en total?
     */
    public function stats(): JsonResponse
    {
        $categories = Category::all();

        $stats = $categories->map(function ($category) {
            return [
                'category'    => $category->name,
                // BAD: cada una de estas líneas ejecuta una query adicional
                'total_posts' => Post::where('category_id', $category->id)->count(),
                'published'   => Post::where('category_id', $category->id)->where('status', 'published')->count(),
                'total_views' => Post::where('category_id', $category->id)->sum('views_count'),
                'avg_comments'=> Post::where('category_id', $category->id)->withCount('comments')->get()->avg('comments_count'),
            ];
        });

        return response()->json($stats);
    }

    // =========================================================
    // PROBLEMA 9: Retorno inconsistente + sin API Resource
    // =========================================================
    /**
     * PREGUNTA: ¿Qué problemas tiene no usar API Resources en Laravel?
     * ¿Qué información sensible podría filtrarse accidentalmente?
     * ¿Cómo se implementa un API Resource y qué ventaja aporta?
     */
    public function getUser(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // BAD: retorna el modelo directamente — expone password (aunque hashed),
        // remember_token, email_verified_at, deleted_at, etc.
        return response()->json($user);
    }

    // =========================================================
    // PROBLEMA 10: Código duplicado + Single Responsibility violado
    // =========================================================
    /**
     * PREGUNTA: Este método tiene responsabilidades mezcladas.
     * ¿Cuántas "razones para cambiar" tiene? ¿Viola el SRP?
     * ¿Cómo lo refactorizarías usando Jobs, Events o Services?
     */
    public function publishPost(int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        // Responsabilidad 1: Validar estado
        if ($post->status === 'published') {
            return response()->json(['error' => 'Already published'], 422);
        }

        // Responsabilidad 2: Cambiar estado (lógica de negocio en controller)
        $post->status       = 'published';
        $post->published_at = now();
        $post->save();

        // Responsabilidad 3: Notificar al autor (debería ser un Event/Job)
        // BAD: envío de email sincrónico en el request — bloquea la respuesta
        // (código omitido pero es el pattern)
        // Mail::to($post->author->email)->send(new PostPublishedMail($post));

        // Responsabilidad 4: Invalidar caché directamente
        \Illuminate\Support\Facades\Cache::forget("post_detail_{$post->slug}");
        \Illuminate\Support\Facades\Cache::forget('featured_posts');

        // Responsabilidad 5: Guardar métricas (debería ser async)
        DB::table('post_publish_log')->insert([
            'post_id'      => $post->id,
            'published_by' => auth()->id() ?? 0,
            'published_at' => now(),
        ]);

        return response()->json($post);
    }
}
