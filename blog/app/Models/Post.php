<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PREGUNTA: ¿Cuál es el problema de performance con el evento 'creating'
 * para generar el slug? ¿Qué ocurre si dos posts con el mismo título
 * se crean al mismo tiempo?
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'scheduled_at',
        'reading_time',
        'is_featured',
        'allow_comments',
        'views_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'published_at'  => 'datetime',
            'scheduled_at'  => 'datetime',
            'is_featured'   => 'boolean',
            'allow_comments'=> 'boolean',
            'meta_keywords' => 'array',
        ];
    }

    // =========================================================================
    // EVENTOS DEL MODELO
    // =========================================================================

    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }

            // PREGUNTA: ¿Qué problema tiene este cálculo de reading_time?
            // ¿Es correcto para contenido HTML? ¿Cómo lo mejorarías?
            if (empty($post->reading_time)) {
                $wordCount = str_word_count(strip_tags($post->content));
                $post->reading_time = (int) ceil($wordCount / 200);
            }
        });
    }

    // =========================================================================
    // RELACIONES
    // =========================================================================

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * PREGUNTA: ¿Qué es una relación polimórfica? Explica cómo funciona
     * morphMany() vs hasMany(). ¿Qué ventajas y desventajas tiene?
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function bookmarkedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bookmarks')->withTimestamps();
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * PREGUNTA: ¿Qué es un Global Scope en Laravel? ¿Podría ser conveniente
     * tener uno aquí para mostrar siempre solo posts publicados por defecto?
     * ¿Qué implicaciones tendría eso?
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image ? asset('storage/' . $this->featured_image) : null;
    }
}
