<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * PREGUNTA: ¿Qué implica no tener $guarded = [] vs tener $fillable explícito?
 * ¿Cuál es la diferencia en términos de seguridad (Mass Assignment)?
 * ¿Cuándo es aceptable usar $guarded = []?
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'bio',
        'role',
        'is_active',
        'website',
        'twitter_handle',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // =========================================================================
    // RELACIONES
    // =========================================================================

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'bookmarks')->withTimestamps();
    }

    /**
     * Usuarios que este usuario sigue
     * PREGUNTA: ¿Cómo funciona una relación self-referential en Eloquent?
     * ¿Por qué se necesitan los alias 'follower_id' y 'following_id'?
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'follower_id',
            'following_id'
        )->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'following_id',
            'follower_id'
        )->withTimestamps();
    }

    // =========================================================================
    // ACCESSORS / MUTATORS
    // =========================================================================

    /**
     * PREGUNTA: ¿Qué diferencia hay entre un Accessor en Laravel 8 y Laravel 9+?
     * ¿Qué ventaja tiene la nueva sintaxis con Attribute::make()?
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name);
    }

    // =========================================================================
    // HELPERS / SCOPES
    // =========================================================================

    /**
     * PREGUNTA: ¿Qué son los Query Scopes en Laravel?
     * ¿Cuál es la diferencia entre un local scope y un global scope?
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return in_array($this->role, ['admin', 'editor']);
    }
}
