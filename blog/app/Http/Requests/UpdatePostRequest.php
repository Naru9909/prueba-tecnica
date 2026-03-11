<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');
        $user = $this->user();

        // Solo el autor o un editor/admin puede actualizar
        return $user && ($post->user_id === $user->id || $user->isEditor());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id'      => ['sometimes', 'integer', 'exists:categories,id'],
            'title'            => ['sometimes', 'string', 'min:5', 'max:255'],
            'content'          => ['sometimes', 'string', 'min:100'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'featured_image'   => ['nullable', 'string', 'max:255'],
            'status'           => ['sometimes', 'in:draft,published,archived,scheduled'],
            'published_at'     => ['nullable', 'date'],
            'is_featured'      => ['nullable', 'boolean'],
            'allow_comments'   => ['nullable', 'boolean'],
            'meta_title'       => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords'    => ['nullable', 'array'],
            'meta_keywords.*'  => ['string', 'max:50'],
            'tag_ids'          => ['nullable', 'array'],
            'tag_ids.*'        => ['integer', 'exists:tags,id'],
        ];
    }
}
