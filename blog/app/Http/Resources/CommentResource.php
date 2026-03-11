<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'body'       => $this->body,
            'status'     => $this->status,
            'is_edited'  => $this->is_edited,
            'edited_at'  => $this->edited_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'user'       => new UserResource($this->whenLoaded('user')),
            'replies'    => CommentResource::collection($this->whenLoaded('replies')),
            'likes_count'=> $this->whenCounted('likes'),
        ];
    }
}
