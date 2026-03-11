<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * PREGUNTA: ¿Por qué se usa un Form Request en lugar de validar en el controller?
 * ¿Qué ventajas tiene en términos de SRP y reutilización?
 * ¿Qué método del FormRequest se podría usar para transformar los datos antes
 * de que lleguen a las reglas de validación? (pista: prepareForValidation)
 */
class StorePostRequest extends FormRequest
{
    /**
     * PREGUNTA: ¿Qué hace el método authorize()?
     * ¿Cuándo retornaría false? ¿Qué código HTTP devuelve Laravel si retorna false?
     */
    public function authorize(): bool
    {
        // Solo editores y admins pueden crear posts
        return $this->user() && $this->user()->isEditor();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id'      => ['required', 'integer', 'exists:categories,id'],
            'title'            => ['required', 'string', 'min:5', 'max:255'],
            'content'          => ['required', 'string', 'min:100'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'featured_image'   => ['nullable', 'string', 'max:255'],
            'status'           => ['nullable', 'in:draft,published,scheduled'],
            'published_at'     => ['nullable', 'date', 'required_if:status,published'],
            'is_featured'      => ['nullable', 'boolean'],
            'allow_comments'   => ['nullable', 'boolean'],
            'meta_title'       => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords'    => ['nullable', 'array'],
            'meta_keywords.*'  => ['string', 'max:50'],
            // PREGUNTA: ¿Qué hace la regla 'exists:tags,id'?
            // ¿Qué sucede si se manda un tag_id que no existe?
            'tag_ids'          => ['nullable', 'array'],
            'tag_ids.*'        => ['integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'content.min'        => 'El contenido debe tener al menos 100 caracteres.',
            'published_at.required_if' => 'La fecha de publicación es requerida cuando el estado es "published".',
        ];
    }
}
