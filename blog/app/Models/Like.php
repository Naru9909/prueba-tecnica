<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * PREGUNTA: ¿Cuál es la diferencia entre una relación polimórfica y una
 * relación regular? ¿Qué trade-offs hay en términos de integridad referencial?
 * ¿Se puede tener una FK en una relación polimórfica? ¿Por qué o por qué no?
 */
class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'likeable_id',
        'likeable_type',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * El modelo que recibió el like (Post o Comment)
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }
}
