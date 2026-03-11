<?php

namespace App\DTOs;

/**
 * PREGUNTA: ¿Cuál es el propósito de un DTO (Data Transfer Object)?
 * ¿En qué se diferencia de un Model de Eloquent?
 * ¿Cuándo es conveniente usar DTOs vs pasar directamente el array del Request?
 *
 * PREGUNTA BONUS: PHP 8 introdujo los Named Arguments y los Constructor Promotion.
 * ¿Qué ventajas tienen para los DTOs vs el estilo tradicional con setters/getters?
 */
final class CreatePostDTO
{
    public function __construct(
        public readonly int    $userId,
        public readonly int    $categoryId,
        public readonly string $title,
        public readonly string $content,
        public readonly ?string $excerpt      = null,
        public readonly ?string $featuredImage = null,
        public readonly string  $status       = 'draft',
        public readonly ?string $publishedAt  = null,
        public readonly bool    $isFeatured   = false,
        public readonly bool    $allowComments = true,
        public readonly ?string $metaTitle    = null,
        public readonly ?string $metaDesc     = null,
        /** @var array<string>|null */
        public readonly ?array  $metaKeywords = null,
        /** @var array<int>|null  ids de tags */
        public readonly ?array  $tagIds       = null,
    ) {}

    /**
     * PREGUNTA: ¿Por qué es útil tener un método estático fromRequest()?
     * ¿Qué ventaja tiene respecto a instanciar el DTO directamente en el controller?
     * ¿Dónde debería vivir la lógica de validación — en el DTO o en el FormRequest?
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            userId:        (int) $data['user_id'],
            categoryId:    (int) $data['category_id'],
            title:         $data['title'],
            content:       $data['content'],
            excerpt:       $data['excerpt']       ?? null,
            featuredImage: $data['featured_image'] ?? null,
            status:        $data['status']         ?? 'draft',
            publishedAt:   $data['published_at']   ?? null,
            isFeatured:    (bool) ($data['is_featured']    ?? false),
            allowComments: (bool) ($data['allow_comments'] ?? true),
            metaTitle:     $data['meta_title']     ?? null,
            metaDesc:      $data['meta_description'] ?? null,
            metaKeywords:  $data['meta_keywords']  ?? null,
            tagIds:        $data['tag_ids']         ?? null,
        );
    }

    /**
     * PREGUNTA: ¿Por qué los DTOs deberían ser inmutables (readonly)?
     * ¿Qué problema puede surgir si un DTO es mutable y se pasa entre capas?
     */
    public function toArray(): array
    {
        return [
            'user_id'          => $this->userId,
            'category_id'      => $this->categoryId,
            'title'            => $this->title,
            'content'          => $this->content,
            'excerpt'          => $this->excerpt,
            'featured_image'   => $this->featuredImage,
            'status'           => $this->status,
            'published_at'     => $this->publishedAt,
            'is_featured'      => $this->isFeatured,
            'allow_comments'   => $this->allowComments,
            'meta_title'       => $this->metaTitle,
            'meta_description' => $this->metaDesc,
            'meta_keywords'    => $this->metaKeywords,
        ];
    }
}
