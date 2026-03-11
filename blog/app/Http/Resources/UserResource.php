<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'username'        => $this->username,
            'bio'             => $this->bio,
            'avatar_url'      => $this->avatar_url,
            'role'            => $this->role,
            'website'         => $this->website,
            'twitter_handle'  => $this->twitter_handle,
            'posts_count'     => $this->whenCounted('posts'),
            'followers_count' => $this->whenCounted('followers'),
            'created_at'      => $this->created_at->toIso8601String(),
            // NOTA: 'password', 'remember_token', 'email' NO se exponen aquí
            // PREGUNTA: ¿Por qué no se incluye el 'email' en un endpoint público de perfil?
        ];
    }
}
