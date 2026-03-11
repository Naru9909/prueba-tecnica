<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PREGUNTA: Este modelo implementa el patrón "Audit Log" / "Event Sourcing light".
 * ¿Qué consideraciones de almacenamiento habría que tener para una tabla que
 * crece sin límite? ¿Qué estrategia de archivado/purga usarías?
 */
class PostRevision extends Model
{
    const UPDATED_AT = null; // Solo tiene created_at

    protected $fillable = [
        'post_id',
        'user_id',
        'title',
        'content',
        'excerpt',
        'revision_number',
        'change_summary',
    ];

    protected function casts(): array
    {
        return [
            'created_at'      => 'datetime',
            'revision_number' => 'integer',
        ];
    }

    // =========================================================================
    // RELACIONES
    // =========================================================================

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
