<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PREGUNTA: ¿Qué es un API Resource en Laravel?
 * ¿Por qué es preferible a retornar $model->toArray() o response()->json($model)?
 * ¿Qué información sensible podría filtrarse si no usamos Resources?
 *
 * PREGUNTA: ¿Qué hace el método whenLoaded()? ¿Cuándo es útil?
 * ¿Qué problema previene respecto al N+1?
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'excerpt'     => $this->excerpt,

            // Solo incluye 'content' si se cargó explícitamente
            // PREGUNTA: ¿Cómo podrías incluir el content solo en el endpoint show() y NO en index()?
            'content'     => $this->when(
                $request->routeIs('posts.show'),
                $this->content
            ),

            'status'         => $this->status,
            'is_featured'    => $this->is_featured,
            'allow_comments' => $this->allow_comments,
            'reading_time'   => $this->reading_time,
            'views_count'    => $this->views_count,
            'published_at'   => $this->published_at?->toIso8601String(),
            'created_at'     => $this->created_at->toIso8601String(),
            'updated_at'     => $this->updated_at->toIso8601String(),

            'featured_image_url' => $this->featured_image_url,

            // whenLoaded: solo incluye la relación si fue cargada con eager loading
            'author'   => new UserResource($this->whenLoaded('author')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'tags'     => TagResource::collection($this->whenLoaded('tags')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),

            // Meta SEO
            'meta' => [
                'title'       => $this->meta_title ?? $this->title,
                'description' => $this->meta_description ?? $this->excerpt,
                'keywords'    => $this->meta_keywords ?? [],
            ],
        ];
    }
}
