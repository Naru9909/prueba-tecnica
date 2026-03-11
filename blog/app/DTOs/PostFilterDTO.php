<?php

namespace App\DTOs;

final class PostFilterDTO
{
    public function __construct(
        public readonly ?int    $categoryId = null,
        public readonly ?int    $tagId      = null,
        public readonly ?int    $authorId   = null,
        public readonly ?string $search     = null,
        public readonly string  $status     = 'published',
        public readonly string  $sortBy     = 'published_at',
        public readonly string  $sortDir    = 'desc',
        public readonly int     $perPage    = 15,
    ) {}

    /**
     * PREGUNTA: ¿Cuál es la ventaja de usar un FilterDTO en lugar de pasar
     * los parámetros del Request directamente al repositorio?
     * ¿Cómo mejora esto la testabilidad del código?
     */
    public static function fromRequest(array $data): self
    {
        $allowedSorts = ['published_at', 'title', 'views_count', 'created_at'];
        $sortBy = in_array($data['sort_by'] ?? null, $allowedSorts)
            ? $data['sort_by']
            : 'published_at';

        return new self(
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            tagId:      isset($data['tag_id'])       ? (int) $data['tag_id']      : null,
            authorId:   isset($data['author_id'])    ? (int) $data['author_id']   : null,
            search:     $data['search']              ?? null,
            status:     $data['status']              ?? 'published',
            sortBy:     $sortBy,
            sortDir:    ($data['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
            perPage:    min((int) ($data['per_page'] ?? 15), 100),
        );
    }
}
