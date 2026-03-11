<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PREGUNTA: ¿Qué estrategia de carga (eager loading) usarías para obtener
 * comentarios con sus respuestas (replies) de forma eficiente?
 * ¿Qué ocurre con la performance si hay 5 niveles de anidamiento?
 */
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'body',
        'status',
        'is_edited',
        'edited_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'is_edited' => 'boolean',
            'edited_at' => 'datetime',
        ];
    }

    // =========================================================================
    // RELACIONES
    // =========================================================================

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Comentario padre (en caso de ser una respuesta)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Respuestas directas a este comentario
     * PREGUNTA: ¿Cómo cargarías todos los niveles de replies de forma recursiva
     * sin generar N+1 queries? ¿Existe un helper en Laravel para esto?
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
