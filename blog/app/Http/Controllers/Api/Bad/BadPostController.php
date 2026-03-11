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
 * Temas evaluados:
 *   - SQL en el ORM: COUNT, SUM, MAX, AVG en relaciones Eloquent
 *   - Propiedades de modelos: $fillable, $guarded, $appends, $hidden
 *   - Autorización: Gates vs Policies, can(), permisos
 *   - Optimización de búsquedas: indexación de columnas, métodos de búsqueda
 */
class BadPostController extends Controller
{
    // =========================================================
    // PROBLEMA 1: N+1 Query — COUNT en relaciones
    // =========================================================
    /**
     * TEMA: SQL en el ORM — Contar en una relación
     *
     * PREGUNTAS:
     *   a) ¿Cuántas queries se ejecutan si hay 20 posts? Desglosalo.
     *   b) ¿Cuál es la diferencia entre `$post->comments->count()` y
     *      `$post->comments()->count()`? ¿Cuál genera menos queries?
     *   c) Eloquent ofrece `withCount()` para contar relaciones en una
     *      sola query. ¿Cómo reescribirías el listado usando `withCount('comments')`
     *      y accediendo a `$post->comments_count`?
     *   d) Si además quisieras mostrar el total de likes de cada post,
     *      ¿agregarías otro `withCount` o usarías `withSum`? Explica cuándo
     *      usar cada uno.
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
    // PROBLEMA 2: SQL Injection + Optimización de búsqueda
    // =========================================================
    /**
     * TEMA: Optimización de búsquedas en APIs — Métodos de búsqueda
     *
     * PREGUNTAS:
     *   a) ¿Qué vulnerabilidad introduce la interpolación directa en el SQL raw?
     *      Escribe un payload que la explotaría (ej.: en el parámetro ?q=).
     *   b) ¿Cómo reescribirías esta búsqueda usando el Query Builder de Laravel
     *      con parámetros enlazados (bindings) para evitar la inyección?
     *   c) La columna `title` no tiene índice. Si la tabla tiene 500 000 registros,
     *      ¿qué tipo de índice crearías en una migración para acelerar búsquedas
     *      con LIKE? ¿Funciona un índice B-Tree normal con LIKE '%term%'? ¿Cuándo
     *      conviene usar FULLTEXT en MySQL o un motor externo como Meilisearch/Scout?
     *   d) ¿Qué columnas del modelo Post indexarías además de `title` para optimizar
     *      los filtros más comunes de una API de blog (por status, category_id, fecha)?
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
    // PROBLEMA 3: Mass Assignment — $fillable vs $guarded
    // =========================================================
    /**
     * TEMA: Propiedades de modelos — $fillable y $guarded
     *
     * PREGUNTAS:
     *   a) `Post::create($request->all())` es una vulnerabilidad de Mass Assignment.
     *      Explica qué es y cómo un atacante podría abusar de ella en este contexto
     *      (ej.: ¿qué campo podría inyectar para escalar privilegios?).
     *   b) ¿Cuál es la diferencia entre declarar `$fillable` y declarar `$guarded`
     *      en un modelo Eloquent? ¿Cuándo preferirías uno sobre el otro?
     *   c) Si el modelo Post tuviera `protected $guarded = []`, ¿estaría protegido
     *      contra Mass Assignment? Justifica tu respuesta.
     *   d) Además del Mass Assignment, identifica otros 3 problemas en este método
     *      (validación, código HTTP de respuesta, lógica de negocio en el controller).
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
    // PROBLEMA 4: Autorización ausente — Gates vs Policies
    // =========================================================
    /**
     * TEMA: Propiedades de modelos — Gate, permission, policy
     *
     * PREGUNTAS:
     *   a) ¿Qué ocurre si el usuario con id=5 envía DELETE /posts/1
     *      siendo que ese post pertenece al usuario con id=2?
     *   b) ¿Cuál es la diferencia entre un Gate y una Policy en Laravel?
     *      ¿Cuándo usarías cada uno? Da un ejemplo concreto de cada caso.
     *   c) Implementa el método `delete` en una PostPolicy que permita
     *      borrar solo si el usuario es el autor del post o tiene rol 'admin'.
     *   d) ¿Cómo registrarías esa Policy y cómo la invocarías en este controller
     *      usando `$this->authorize()` o el helper `can()`?
     *   e) Si el proyecto usa un paquete de permisos (ej.: Spatie Permissions),
     *      ¿cómo combinarías los permisos del paquete con una Policy de Laravel?
     */
    public function destroy(int $id): JsonResponse
    {
        // BAD: Cualquier usuario autenticado puede borrar CUALQUIER post
        $post = Post::findOrFail($id);
        $post->delete();

        return response()->json(['message' => 'deleted']);
    }

    // =========================================================
    // PROBLEMA 5: Over-fetching + Índices en claves foráneas
    // =========================================================
    /**
     * TEMA: Optimización de búsquedas en APIs — Indexación y selección de columnas
     *
     * PREGUNTAS:
     *   a) Se cargan 7 relaciones incluyendo `revisions` y `comments.replies.user`.
     *      ¿Qué columnas pesadas se están trayendo innecesariamente?
     *      ¿Cómo usarías `select()` dentro del eager load para traer solo
     *      los campos necesarios (ej.: `with(['author:id,name'])`)?
     *   b) Las claves foráneas como `post_id`, `user_id`, `category_id` deben
     *      tener índices. ¿Por qué? ¿Qué tipo de índice crea Laravel automáticamente
     *      al usar `$table->foreignId('category_id')->constrained()` en una migración?
     *   c) ¿Cuándo es contraproducente agregar un índice a una columna?
     *      Menciona al menos dos casos en que el índice perjudica el rendimiento.
     *   d) ¿Qué herramienta o comando usarías en MySQL para verificar si una query
     *      está usando un índice? (pista: EXPLAIN)
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
    // PROBLEMA 6: XSS + $fillable en Comment + sin validación
    // =========================================================
    /**
     * TEMA: Propiedades de modelos — $fillable, y seguridad de input
     *
     * PREGUNTAS:
     *   a) El campo `body` puede contener `<script>alert('xss')</script>`.
     *      ¿Cuál es la diferencia entre sanitizar el input antes de guardarlo
     *      y escapar el output al renderizarlo? ¿Dónde debe ocurrir cada uno?
     *   b) Si el modelo Comment tiene `protected $fillable = ['post_id','user_id','body']`,
     *      ¿qué impacto tiene eso comparado con no tener `$fillable` definido?
     *      ¿Podría un atacante inyectar un `parent_id` o `approved = true` mediante
     *      Mass Assignment si esos campos no están en `$fillable`?
     *   c) ¿Qué validaciones mínimas agregarías con un FormRequest antes de crear
     *      el comentario (longitud, existencia del post, usuario autenticado)?
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
     * TEMA: Integridad de datos — DB::transaction y rollback
     *
     * PREGUNTAS:
     *   a) ¿Qué ocurre si `$post->tags()->sync()` lanza una excepción?
     *      ¿En qué estado queda la base de datos? ¿Cómo lo llaman en teoría de BD?
     *   b) Reescribe el método usando `DB::transaction()` para garantizar atomicidad.
     *   c) ¿Cuál es la diferencia entre `DB::transaction(callable)` y el par
     *      `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()`?
     *      ¿En qué escenario preferirías el enfoque manual?
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
    // PROBLEMA 8: N+1 en estadísticas — SUM, AVG, MAX en el ORM
    // =========================================================
    /**
     * TEMA: SQL en el ORM — SUM, AVG, MAX, COUNT en relaciones
     *
     * PREGUNTAS:
     *   a) Si hay 100 categorías, ¿cuántas queries se ejecutan en total?
     *      Desglosalo por cada llamada dentro del `map()`.
     *   b) Eloquent ofrece `withSum`, `withAvg`, `withMax` y `withCount` para
     *      calcular agregados en una sola query de JOIN. Reescribe este método
     *      usando esos métodos en Category::withCount/withSum/withAvg.
     *      Ejemplo esperado: `Category::withCount('posts') ...->get()`.
     *   c) ¿Cuándo usarías `withMax('posts', 'views_count')` vs hacer un
     *      `Post::where('category_id', $id)->max('views_count')` por separado?
     *      ¿Qué caso de uso práctico tiene el `MAX` en un API de blog?
     *   d) ¿Puedes combinar múltiples agregados (count + sum + avg) en una sola
     *      llamada a Eloquent? Muestra la sintaxis correcta.
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
    // PROBLEMA 9: Exposición de datos — $hidden, $appends y API Resources
    // =========================================================
    /**
     * TEMA: Propiedades de modelos — $appends y $hidden
     *
     * PREGUNTAS:
     *   a) Al retornar `$user` directamente, ¿qué campos sensibles se exponen?
     *      Lista al menos 4 campos del modelo User que nunca deberían salir en la API.
     *   b) La propiedad `$hidden` en un modelo Eloquent oculta campos de la
     *      serialización JSON. ¿Cómo la configurarías en el modelo User para
     *      ocultar `password` y `remember_token`?
     *      ¿Es suficiente `$hidden` para proteger datos sensibles, o se puede
     *      saltear? ¿Cómo?
     *   c) La propiedad `$appends` permite incluir atributos calculados (accessors)
     *      en la serialización. Escribe un ejemplo: un accessor `full_name` en el
     *      modelo User que concatene `first_name` y `last_name`, y cómo lo
     *      expondrías via `$appends`.
     *   d) ¿Qué ventaja tiene un API Resource de Laravel sobre confiar en `$hidden`
     *      y `$appends` directamente? ¿Cuándo usarías cada enfoque?
     */
    public function getUser(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // BAD: retorna el modelo directamente — expone password (aunque hashed),
        // remember_token, email_verified_at, deleted_at, etc.
        return response()->json($user);
    }

    // =========================================================
    // PROBLEMA 10: SRP violado — Autorización, caché y eventos mezclados
    // =========================================================
    /**
     * TEMA: Gate / Policy + SRP
     *
     * PREGUNTAS:
     *   a) Este método tiene 5 responsabilidades distintas. Listalas y explica
     *      por qué cada una debería estar en una capa diferente
     *      (Policy, Service, Event/Job, Observer).
     *   b) La verificación del estado `published` es una regla de negocio.
     *      ¿Cómo la moverías a una Policy? ¿Qué método de la Policy
     *      (ej.: `publish`) invocaría el controller con `$this->authorize('publish', $post)`?
     *   c) ¿Qué diferencia hay entre controlar acceso con un Gate definido en
     *      `AppServiceProvider::boot()` via `Gate::define()` y hacerlo con una Policy
     *      asociada al modelo? ¿Cuándo escala mejor cada opción?
     *   d) La invalidación de caché y el log de publicación deberían ejecutarse
     *      de forma asíncrona. ¿Qué mecanismo de Laravel usarías
     *      (Events + Listeners, Jobs, Observers) y por qué?
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
