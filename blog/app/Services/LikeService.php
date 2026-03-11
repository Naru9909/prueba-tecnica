<?php

namespace App\Services;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * PREGUNTA: ¿Por qué este service recibe un Model genérico en vez de Post|Comment?
 * ¿Qué patrón permite que Like sea polimórfico?
 */
class LikeService
{
    /**
     * PREGUNTA: Este método usa firstOrCreate. ¿Cuál es la diferencia con
     * firstOrNew, create, y updateOrCreate?
     * ¿Hay algún problema de race condition con firstOrCreate? ¿Cómo lo resolverías?
     */
    public function toggle(User $user, Model $likeable): array
    {
        $existing = Like::where('user_id', $user->id)
            ->where('likeable_id', $likeable->id)
            ->where('likeable_type', get_class($likeable))
            ->first();

        if ($existing) {
            $existing->delete();
            return ['liked' => false, 'count' => $likeable->likes()->count()];
        }

        Like::create([
            'user_id'       => $user->id,
            'likeable_id'   => $likeable->id,
            'likeable_type' => get_class($likeable),
        ]);

        return ['liked' => true, 'count' => $likeable->likes()->count()];
    }

    public function isLikedBy(User $user, Model $likeable): bool
    {
        return Like::where('user_id', $user->id)
            ->where('likeable_id', $likeable->id)
            ->where('likeable_type', get_class($likeable))
            ->exists();
    }
}
