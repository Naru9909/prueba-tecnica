<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * PREGUNTA: ¿Cómo se diferencian withPivot() y withTimestamps() en una
     * relación BelongsToMany? ¿Cuándo necesitarías usar withPivot()?
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tag')->withTimestamps();
    }
}
