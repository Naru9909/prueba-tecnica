<?php

namespace App\DTOs;

/**
 * DTO para actualizar un post existente.
 *
 * PREGUNTA: ¿Cuál es la diferencia entre un DTO de creación y uno de actualización?
 * ¿Por qué todos los campos son nullable en el UpdatePostDTO pero no en el CreatePostDTO?
 * ¿Qué patrón de diseño representa esto? (pista: "partial update" / PATCH vs PUT)
 */
final class UpdatePostDTO
{
    public function __construct(
        public readonly ?int    $categoryId    = null,
        public readonly ?string $title         = null,
        public readonly ?string $content       = null,
        public readonly ?string $excerpt       = null,
        public readonly ?string $featuredImage = null,
        public readonly ?string $status        = null,
        public readonly ?string $publishedAt   = null,
        public readonly ?bool   $isFeatured    = null,
        public readonly ?bool   $allowComments = null,
        public readonly ?string $metaTitle     = null,
        public readonly ?string $metaDesc      = null,
        public readonly ?array  $metaKeywords  = null,
        public readonly ?array  $tagIds        = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            categoryId:    isset($data['category_id'])    ? (int) $data['category_id'] : null,
            title:         $data['title']                 ?? null,
            content:       $data['content']               ?? null,
            excerpt:       $data['excerpt']               ?? null,
            featuredImage: $data['featured_image']        ?? null,
            status:        $data['status']                ?? null,
            publishedAt:   $data['published_at']          ?? null,
            isFeatured:    isset($data['is_featured'])    ? (bool) $data['is_featured'] : null,
            allowComments: isset($data['allow_comments']) ? (bool) $data['allow_comments'] : null,
            metaTitle:     $data['meta_title']            ?? null,
            metaDesc:      $data['meta_description']      ?? null,
            metaKeywords:  $data['meta_keywords']         ?? null,
            tagIds:        $data['tag_ids']               ?? null,
        );
    }

    /**
     * PREGUNTA: Este método solo retorna los campos que no son null.
     * ¿Por qué es importante este comportamiento en un PATCH endpoint?
     * ¿Qué problema tendría si se incluyeran los nulls en un update de Eloquent?
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->categoryId    !== null) $data['category_id']      = $this->categoryId;
        if ($this->title         !== null) $data['title']             = $this->title;
        if ($this->content       !== null) $data['content']           = $this->content;
        if ($this->excerpt       !== null) $data['excerpt']           = $this->excerpt;
        if ($this->featuredImage !== null) $data['featured_image']    = $this->featuredImage;
        if ($this->status        !== null) $data['status']            = $this->status;
        if ($this->publishedAt   !== null) $data['published_at']      = $this->publishedAt;
        if ($this->isFeatured    !== null) $data['is_featured']       = $this->isFeatured;
        if ($this->allowComments !== null) $data['allow_comments']    = $this->allowComments;
        if ($this->metaTitle     !== null) $data['meta_title']        = $this->metaTitle;
        if ($this->metaDesc      !== null) $data['meta_description']  = $this->metaDesc;
        if ($this->metaKeywords  !== null) $data['meta_keywords']     = $this->metaKeywords;

        return $data;
    }
}
