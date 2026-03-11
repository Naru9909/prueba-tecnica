<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PREGUNTA: Este modelo tiene una relación recursiva (categoría padre/hijo).
 * ¿Cómo cargarías todos los niveles de subcategorías de forma eficiente?
 * ¿Existe algún paquete de Laravel que facilite árboles jerárquicos?
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // =========================================================================
    // RELACIONES
    // =========================================================================

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * PREGUNTA: ¿Qué problema de N+1 puede ocurrir con este método
     * si se llama en un loop para una lista de categorías?
     */
    public function getPostsCountAttribute(): int
    {
        return $this->posts()->count();
    }
}
