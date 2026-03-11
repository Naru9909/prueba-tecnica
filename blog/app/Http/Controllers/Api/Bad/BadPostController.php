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
     *
     * RESPUESTAS:
     *   a) 1 (Post::all) + 20 (author) + 20 (category) + 20 (tags) + 20 (comments) = 81 queries.
     *      Con N posts genéricamente: 1 + 4N queries.
     *
     *   b) `$post->comments->count()` → accede a la relación como propiedad: Eloquent carga
     *      TODOS los comentarios en una Collection PHP y luego PHP hace el count() en memoria.
     *      Genera 1 query que trae todas las filas.
     *      `$post->comments()->count()` → llama a la relación como método (devuelve QueryBuilder)
     *      y ejecuta SELECT COUNT(*) ... en la base de datos. Más eficiente: 1 query ligera.
     *      El segundo es correcto cuando solo necesitas el número.
     *
     *   c) Versión correcta usando withCount y eager loading con columnas específicas:
     *
     *      // RESPUESTA ESPERADA:
     *      $posts = Post::select(['id', 'title', 'user_id', 'category_id'])
     *          ->with(['author:id,name', 'category:id,name', 'tags:id,name'])
     *          ->withCount('comments')
     *          ->paginate(15);
     *
     *      return PostResource::collection($posts);
     *      // Acceso en la colección: $post->comments_count
     *
     *   d) `withCount('likes')` → cuenta el número de filas en la tabla likes relacionadas.
     *      Útil si cada fila = 1 like (sin valor numérico).
     *      `withSum('likes', 'value')` → suma el campo `value` de las filas relacionadas.
     *      Útil si likes tiene +1/-1 (sistema de votos). Acceso: $post->likes_sum_value.
     *      Regla: withCount para contar registros, withSum para sumar un campo numérico.
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
     *
     * RESPUESTAS:
     *   a) SQL Injection. El payload se inyecta en ?q=.
     *      Ejemplo para leer datos:
     *        ?q=%' UNION SELECT id,password,email FROM users -- -
     *      Ejemplo para destruir:
     *        ?q=%'; DROP TABLE posts; -- -
     *
     *   b) Corrección con bindings (nunca interpolar input del usuario en SQL raw):
     *
     *      // OPCIÓN 1 — Query Builder (recomendada, siempre usa PDO bindings):
     *      $posts = Post::select('id', 'title', 'excerpt')
     *          ->where('title', 'like', '%' . $term . '%')
     *          ->get();
     *
     *      // OPCIÓN 2 — DB::select con binding posicional:
     *      $posts = DB::select(
     *          'SELECT id, title, excerpt FROM posts WHERE title LIKE ?',
     *          ["%{$term}%"]
     *      );
     *
     *   c) Un índice B-Tree NO acelera LIKE '%term%' (wildcard al inicio desactiva el índice).
     *      Sí acelera LIKE 'term%' (prefijo fijo), que es un caso menos común en búsqueda.
     *      Para búsqueda de texto libre usar:
     *        - FULLTEXT index en MySQL: $table->fullText('title') en la migración;
     *          Query: Post::whereFullText('title', $term)->get();
     *        - Para mayor escala o búsqueda multifield: Laravel Scout + Meilisearch/Algolia.
     *          Scout indexa los modelos y delega la búsqueda al motor externo optimizado.
     *
     *   d) Columnas a indexar en posts para filtros frecuentes:
     *        - status (filtro casi siempre presente)
     *        - category_id (FK, JOIN frecuente)
     *        - user_id / author_id (FK, JOIN frecuente)
     *        - published_at (ordenado DESC en el listado)
     *      Índice compuesto recomendado: (status, published_at) porque el filtro más común
     *      es "publicados ordenados por fecha".
     *      En migración: $table->index(['status', 'published_at']);
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
     *
     * RESPUESTAS:
     *   a) Mass Assignment: Eloquent asigna cualquier campo enviado en el request al modelo
     *      si no hay $fillable o si se usa $request->all().
     *      Abuso concreto: el atacante envía en el body `user_id=1` (robar autoría),
     *      `status=published` (publicar sin revisión) o `is_admin=1` si ese campo existe.
     *      Solución: definir $fillable en el modelo y usar $request->validated().
     *
     *   b) $fillable = lista BLANCA: solo los campos declarados pueden asignarse masivamente.
     *         protected $fillable = ['title', 'content', 'category_id'];
     *      $guarded  = lista NEGRA: todos los campos son asignables EXCEPTO los declarados.
     *         protected $guarded = ['id', 'user_id', 'status'];
     *      Preferir $fillable: es explícito y más seguro ("denegar por defecto").
     *      $guarded es útil en modelos con muchos campos donde listar todos sería verboso.
     *
     *   c) $guarded = [] desactiva POR COMPLETO la protección de Mass Assignment.
     *      El modelo acepta cualquier campo del request. NO está protegido.
     *      Es equivalente a no declarar ninguna propiedad de guarding.
     *
     *   d) Otros 3 problemas:
     *      1) Sin validación: usar un StorePostRequest con $this->validate() o rules().
     *      2) Código HTTP incorrecto: crear recurso debe retornar 201 Created,
     *         no 200 OK: response()->json($post, 201).
     *      3) Lógica de negocio en el controller: el cálculo de reading_time y slug
     *         debe vivir en un PostService o en un Observer del modelo (creating/created).
     *
     *      // CÓDIGO CORRECTO ESPERADO:
     *      public function store(StorePostRequest $request): JsonResponse
     *      {
     *          $post = $this->postService->create($request->validated());
     *          return (new PostResource($post))->response()->setStatusCode(201);
     *      }
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
     *
     * RESPUESTAS:
     *   a) El usuario con id=5 borra el post de id=1 sin ningún problema ya que el método
     *      no verifica si el usuario autenticado es el propietario del recurso.
     *      Es una vulnerabilidad de Broken Object Level Authorization (BOLA/IDOR).
     *
     *   b) Gate: autorización simple sin modelo, definida en AppServiceProvider.
     *         Gate::define('view-reports', fn(User $user) => $user->is_admin);
     *         Usar cuando la regla no está ligada a un modelo específico.
     *      Policy: clase dedicada a un modelo, con métodos por acción (view, create,
     *         update, delete, etc.). Más organizado para recursos REST.
     *         Usar cuando cada acción CRUD tiene reglas diferentes por recurso.
     *
     *   c) Implementación correcta del método delete en PostPolicy:
     *
     *      // app/Policies/PostPolicy.php
     *      public function delete(User $user, Post $post): bool
     *      {
     *          return $user->id === $post->user_id
     *              || $user->role === 'admin';
     *      }
     *
     *   d) Registro (Laravel auto-descubre Policies en App\Policies por convención):
     *      O explícito en AppServiceProvider: Gate::policy(Post::class, PostPolicy::class);
     *
     *      Invocación en el controller (lanza AuthorizationException si falla):
     *         $this->authorize('delete', $post);
     *      O con el helper (retorna bool):
     *         if ($request->user()->cannot('delete', $post)) abort(403);
     *
     *   e) Con Spatie, el modelo User tiene el trait HasRoles.
     *      En el método de la Policy se puede combinar:
     *
     *      public function delete(User $user, Post $post): bool
     *      {
     *          // Permiso granular de Spatie O ser el autor
     *          return $user->hasPermissionTo('delete posts')
     *              || $user->id === $post->user_id;
     *      }
     *
     *      // CÓDIGO CORRECTO EN EL CONTROLLER:
     *      public function destroy(int $id): JsonResponse
     *      {
     *          $post = Post::findOrFail($id);
     *          $this->authorize('delete', $post); // lanza 403 si no pasa la Policy
     *          $post->delete();
     *          return response()->json(null, 204);
     *      }
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
     *
     * RESPUESTAS:
     *   a) Columnas pesadas innecesarias: content (texto largo), revisions.content,
     *      timestamps internos, deleted_at, campos de auditoría.
     *      Se debe limitar columnas dentro del eager load:
     *
     *      // RESPUESTA ESPERADA:
     *      $post = Post::select(['id', 'title', 'excerpt', 'user_id', 'category_id', 'published_at'])
     *          ->with([
     *              'author:id,name',          // solo id y name
     *              'category:id,name',
     *              'tags:id,name',
     *              'comments' => fn($q) => $q->select('id','post_id','user_id','body','created_at')
     *                                        ->with('user:id,name')
     *                                        ->whereNull('parent_id'), // solo top-level
     *          ])
     *          ->findOrFail($id);
     *      // No cargar revisions en show, solo bajo demanda con un endpoint propio.
     *
     *   b) Las FKs necesitan índice porque los JOINs y WHEREs sobre ellas son frecuentes.
     *      Sin índice, cada JOIN escanea la tabla completa (Full Table Scan).
     *      `$table->foreignId('category_id')->constrained()` crea automáticamente
     *      un índice B-Tree estándar sobre la columna category_id.
     *
     *   c) Un índice es contraproducente cuando:
     *      1) La columna tiene muy baja cardinalidad (ej.: status con 2-3 valores):
     *         el motor puede ignorar el índice y hacer full scan de todas formas.
     *      2) Tablas pequeñas (<1000 filas): el overhead de leer el índice B-Tree
     *         puede ser mayor que un scan directo.
     *      3) Columnas que se escriben/actualizan muy frecuentemente: cada INSERT/UPDATE
     *         debe mantener el índice, degradando la escritura.
     *
     *   d) Verificar uso de índice en MySQL:
     *      EXPLAIN SELECT * FROM posts WHERE category_id = 5;
     *      Columnas clave a revisar:
     *        - type: 'ref' o 'range' = usa índice; 'ALL' = Full Table Scan (problema).
     *        - key: nombre del índice utilizado (NULL si no usa ninguno).
     *        - rows: estimación de filas a examinar (menor = más eficiente).
     *      En Laravel se puede usar: DB::enableQueryLog() o el paquete Debugbar/Telescope.
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
     *
     * RESPUESTAS:
     *   a) Sanitizar input = limpiar el dato ANTES de guardarlo en BD
     *      (ej.: strip_tags($body) o usar HTMLPurifier para permitir HTML seguro).
     *      Escapar output = codificar caracteres especiales AL MOSTRARLO en el frontend
     *      (ej.: htmlspecialchars() en PHP, {{ }} en Blade, o que el framework JS lo haga).
     *      Ambas capas son necesarias: sanitizar para no guardar basura, escapar para
     *      no ejecutar lo que pudiera haber pasado el filtro.
     *      En una API JSON pura el riesgo de XSS está en el cliente que renderiza el body;
     *      la API debe documentar que el campo no es HTML seguro o sanitizarlo al guardar.
     *
     *   b) Con $fillable = ['post_id','user_id','body'] definido:
     *         Un atacante que envíe `approved=true` o `parent_id=99` en el body del request
     *         NO puede asignarlo mediante Comment::create([...]) porque esos campos
     *         no están en $fillable. Eloquent los ignorará silenciosamente.
     *      Sin $fillable definido (o con $guarded = []):
     *         Comment::create($request->all()) asignaría aprobado=true u otros campos
     *         sensibles, saltando la lógica de moderación.
     *
     *   c) Validaciones mínimas en StoreCommentRequest:
     *
     *      // RESPUESTA ESPERADA:
     *      public function rules(): array
     *      {
     *          return [
     *              'body' => ['required', 'string', 'min:3', 'max:2000'],
     *              // Verificar que el post exista y esté publicado:
     *              // (post_id viene de la ruta, validarlo contra la BD)
     *          ];
     *      }
     *      public function authorize(): bool
     *      {
     *          // Solo usuarios autenticados y que el post exista
     *          $post = Post::find($this->route('postId'));
     *          return auth()->check() && $post && $post->status === 'published';
     *      }
     *      // En el controller guardar sanitándolo:
     *      $comment = Comment::create([
     *          'post_id' => $postId,
     *          'user_id' => $request->user()->id,
     *          'body'    => strip_tags($request->validated('body')),
     *      ]);
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
     *
     * RESPUESTAS:
     *   a) Si `$post->tags()->sync()` lanza una excepción, el UPDATE del post YA se
     *      guardó en la BD (no hay transacción que lo revierta). La base de datos queda
     *      en estado INCONSISTENTE: el post tiene el nuevo título/contenido pero los
     *      tags siguen siendo los anteriores. En teoría de BD esto viola la propiedad
     *      de ATOMICIDAD (la A de ACID).
     *
     *   b) Versión correcta con DB::transaction:
     *
     *      // RESPUESTA ESPERADA:
     *      public function update(Request $request, int $id): JsonResponse
     *      {
     *          $post = Post::findOrFail($id);
     *          $this->authorize('update', $post);
     *
     *          $post = DB::transaction(function () use ($post, $request) {
     *              $post->update($request->validated());
     *              if ($request->has('tags')) {
     *                  $post->tags()->sync($request->validated('tags'));
     *              }
     *              return $post;
     *          });
     *
     *          return new PostResource($post);
     *      }
     *      // Si sync() lanza excepción, DB::transaction hace rollback automático
     *      // y vuelve a lanzar la excepción para que el handler la transforme en 500.
     *
     *   c) DB::transaction(callable): delega el manejo al framework.
     *      Si el callable lanza cualquier Throwable, hace rollback automático y
     *      relanza la excepción. Simple y seguro para el caso común.
     *
     *      Enfoque manual (beginTransaction / commit / rollBack):
     *      Necesario cuando debes inspeccionar el error antes de decidir si hacer
     *      rollback (ej.: errores esperados que son válidos), o cuando trabajas con
     *      múltiples conexiones de BD que deben coordinarse.
     *
     *      // Ejemplo manual:
     *      DB::beginTransaction();
     *      try {
     *          $post->update($data);
     *          $post->tags()->sync($tags);
     *          DB::commit();
     *      } catch (\Throwable $e) {
     *          DB::rollBack();
     *          throw $e;
     *      }
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
     *
     * RESPUESTAS:
     *   a) Con 100 categorías, dentro del map() por cada categoría se ejecutan:
     *         1 count (total_posts) + 1 count (published) + 1 sum (total_views)
     *         + 1 get() completo + PHP avg (avg_comments) = 4 queries por categoría.
     *      Total: 1 (Category::all) + 100 x 4 = 401 queries.
     *
     *   b) Versión correcta usando agregados de Eloquent en una sola llamada:
     *
     *      // RESPUESTA ESPERADA:
     *      $stats = Category::select('id', 'name')
     *          ->withCount([
     *              'posts as total_posts',
     *              'posts as published' => fn($q) => $q->where('status', 'published'),
     *          ])
     *          ->withSum('posts as total_views', 'views_count')
     *          ->withAvg('posts as avg_comments', 'comments_count')
     *          ->get();
     *      // Resultado: 2 queries en total (1 categories + 1 JOIN con subconsultas agregadas)
     *      // Acceso: $cat->total_posts, $cat->published, $cat->total_views_sum,
     *      //          $cat->avg_comments_avg
     *
     *   c) `withMax('posts', 'views_count')` → cuando necesitas el máximo para TODAS las
     *      categorías en una sola consulta (listado de categorías con su post más visto).
     *      `Post::where(...)->max('views_count')` → cuando necesitas el máximo para
     *      UNA categoría específica (detalle de una categoría).
     *      Caso de uso práctico de MAX en un blog:
     *        - "Post más visto de cada categoría" en un widget de sidebar.
     *        - Dashboard admin: máximo de comentarios para detectar posts virales.
     *        - Normalizar visualizaciones: mostrar barra de progreso relativa al max.
     *
     *   d) Sí, se pueden combinar múltiples agregados en una sola llamada:
     *
     *      Category::withCount('posts')          // -> posts_count
     *          ->withSum('posts', 'views_count')  // -> posts_sum_views_count
     *          ->withAvg('posts', 'views_count')  // -> posts_avg_views_count
     *          ->withMax('posts', 'views_count')  // -> posts_max_views_count
     *          ->withMin('posts', 'views_count')  // -> posts_min_views_count
     *          ->get();
     *      // Genera 2 queries: una para categories y una con todos los JOINs/subconsultas.
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
     *
     * RESPUESTAS:
     *   a) Campos sensibles expuestos al retornar el modelo User directamente:
     *         password (hash bcrypt), remember_token, email_verified_at,
     *         two_factor_secret, two_factor_recovery_codes, deleted_at,
     *         created_at/updated_at (a veces innecesarios).
     *
     *   b) Configurar $hidden en el modelo User:
     *
     *      protected $hidden = ['password', 'remember_token', 'two_factor_secret'];
     *
     *      ¿Se puede saltear $hidden? Sí: llamando a $user->makeVisible('password')
     *      en cualquier lugar del código, o serializando con $user->toArray() en un
     *      contexto que llame a makeVisible antes. Por eso no es suficiente por sí solo
     *      en aplicaciones grandes: un API Resource da control explícito.
     *
     *   c) Accessor con $appends en el modelo User:
     *
     *      // En el modelo:
     *      protected $appends = ['full_name'];
     *
     *      public function getFullNameAttribute(): string
     *      {
     *          return trim($this->first_name . ' ' . $this->last_name);
     *      }
     *      // O con la sintaxis nueva de Eloquent Casts (Laravel 9+):
     *      protected function fullName(): Attribute
     *      {
     *          return Attribute::make(
     *              get: fn() => trim($this->first_name . ' ' . $this->last_name)
     *          );
     *      }
     *      // $appends agrega 'full_name' automaticamente a toArray() y toJson().
     *
     *   d) API Resource: define EXPLÍCITAMENTE qué campos se exponen, campo a campo.
     *      Ventajas sobre $hidden/$appends:
     *        - Diferentes representaciones del mismo modelo (UserResource vs UserDetailResource).
     *        - No depende del estado del modelo (makeVisible no rompe nada).
     *        - Permite transformar, renombrar y agregar datos calculados sin ensuciar el modelo.
     *        - Versionable: v1/UserResource vs v2/UserResource.
     *      Usar $hidden/$appends: para reglas globales simátricas (siempre ocultar password).
     *      Usar API Resource: para el contrato de la API, donde el output puede variar
     *      según el contexto o el rol del usuario.
     *
     *      // RESPUESTA ESPERADA EN EL CONTROLLER:
     *      public function getUser(int $id): JsonResponse
     *      {
     *          return new UserResource(User::findOrFail($id));
     *      }
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
     *
     * RESPUESTAS:
     *   a) Las 5 responsabilidades y la capa correcta para cada una:
     *      1) Verificar que el post no esté ya publicado → PostPolicy (método `publish`).
     *         Es una regla de autorización/negocio sobre el estado del recurso.
     *      2) Cambiar estado y guardar → PostService::publish(Post $post): void.
     *         Lógica de negocio pura, no pertenece al controller.
     *      3) Notificar al autor por email → Queued Listener o Job (async).
     *         Un email nunca debe bloquear la respuesta HTTP.
     *      4) Invalidar caché → Listener del evento PostPublished (async o síncrono).
     *         El controller no debería conocer las claves de caché.
     *      5) Log de publicación → Listener o Observer del modelo (async).
     *         Persistencia de auditoría no debe bloquear el request.
     *
     *   b) Mover la verificación de estado a la Policy:
     *
     *      // app/Policies/PostPolicy.php
     *      public function publish(User $user, Post $post): bool
     *      {
     *          // Solo el autor o admin, y solo si no está ya publicado
     *          return $post->status !== 'published'
     *              && ($user->id === $post->user_id || $user->role === 'admin');
     *      }
     *
     *      // En el controller:
     *      $this->authorize('publish', $post); // lanza 403 (si no puede) o 422 si se quiere
     *      // La lógica de "ya publicado" está encapsulada en la Policy.
     *
     *   c) Gate::define() en AppServiceProvider: útil para reglas simples o globales
     *      que no están ligadas a un modelo concreto, o para permisos de administración.
     *      Escala mal cuando hay muchas acciones por recurso (un define por acción).
     *      Policy: escala mejor en APIs REST: un archivo por modelo, un método por acción.
     *      Autorid por convención con `php artisan make:policy`.
     *      Regla práctica: usar Gate para reglas de 1-2 líneas sin modelo,
     *      Policy para cualquier recurso CRUD.
     *
     *   d) Mecanismo recomendado: Events + Queued Listeners.
     *      El controller dispara un evento (PostPublished), los Listeners reaccionan
     *      de forma desacoplada y en cola (async). Ventajas:
     *        - El controller solo sabe que "algo pasó", no cómo se maneja.
     *        - Fácil agregar nuevas acciones (nuevo Listener) sin tocar el controller.
     *        - Las colas (Redis/database) procesan notificaciones sin bloquear el request.
     *      Observer: útil para reaccionar a eventos de ciclo de vida del modelo (saved,
     *        deleted) independientemente de dónde ocurra el cambio. Bueno para auditoría.
     *      Job: cuando la operación es una unidad de trabajo pesada y auto-contenida
     *        (ej.: generar PDF, enviar email masivo). Los Listeners pueden despacharlos.
     *
     *      // CÓDIGO CORRECTO ESPERADO EN EL CONTROLLER:
     *      public function publishPost(int $id): JsonResponse
     *      {
     *          $post = Post::findOrFail($id);
     *          $this->authorize('publish', $post);
     *          $this->postService->publish($post); // cambia estado + dispara evento
     *          return new PostResource($post->refresh());
     *      }
     *      // PostService::publish() → cambia status, guarda, dispara PostPublished
     *      // PostPublished listeners: SendPostPublishedNotification (queued),
     *      //   InvalidatePostCache (queued), LogPostPublication (queued)
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
