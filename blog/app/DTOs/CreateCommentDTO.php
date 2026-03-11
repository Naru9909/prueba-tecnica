<?php

namespace App\DTOs;

final class CreateCommentDTO
{
    public function __construct(
        public readonly int    $postId,
        public readonly int    $userId,
        public readonly string $body,
        public readonly ?int   $parentId  = null,
        public readonly string $ipAddress = '',
    ) {}

    public static function fromRequest(array $data, int $userId, string $ip): self
    {
        return new self(
            postId:    (int) $data['post_id'],
            userId:    $userId,
            body:      $data['body'],
            parentId:  isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            ipAddress: $ip,
        );
    }

    public function toArray(): array
    {
        return [
            'post_id'    => $this->postId,
            'user_id'    => $this->userId,
            'body'       => $this->body,
            'parent_id'  => $this->parentId,
            'ip_address' => $this->ipAddress,
        ];
    }
}
