<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * PREGUNTA: ¿Qué hace el método configure() en una Factory?
 * ¿Cuándo usarías afterCreating() vs afterMaking()?
 *
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(fake()->numberBetween(4, 10));
        $title = rtrim($title, '.'); // Quitar el punto final del fake sentence

        $content = $this->generateTechContent();

        return [
            'user_id'          => User::factory(),
            'category_id'      => Category::factory(),
            'title'            => $title,
            'slug'             => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 99999),
            'excerpt'          => fake()->paragraph(2),
            'content'          => $content,
            'featured_image'   => null,
            'status'           => 'draft',
            'published_at'     => null,
            'scheduled_at'     => null,
            'reading_time'     => (int) ceil(str_word_count(strip_tags($content)) / 200),
            'is_featured'      => false,
            'allow_comments'   => true,
            'views_count'      => 0,
            'meta_title'       => null,
            'meta_description' => null,
            'meta_keywords'    => null,
        ];
    }

    // =========================================================================
    // STATES
    // =========================================================================

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'published',
            'published_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'published',
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'is_featured'  => true,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => 'archived',
            'published_at' => fake()->dateTimeBetween('-3 years', '-1 year'),
        ]);
    }

    public function withViews(int $min = 100, int $max = 50000): static
    {
        return $this->state(fn (array $attributes) => [
            'views_count' => fake()->numberBetween($min, $max),
        ]);
    }

    public function withMeta(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_title'       => fake()->sentence(6),
            'meta_description' => fake()->sentence(20),
            'meta_keywords'    => fake()->words(5),
        ]);
    }

    // =========================================================================
    // AFTER CREATING
    // =========================================================================

    /**
     * PREGUNTA: ¿Qué hace configure() con afterCreating()?
     * ¿Por qué es necesario para sincronizar los tags (relación ManyToMany)?
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Post $post) {
            // Los tags se asignan en el Seeder para controlar la distribución
        });
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function generateTechContent(): string
    {
        $sections = [
            "## Introducción\n\n" . fake()->paragraphs(3, true),
            "\n\n## El Problema\n\n" . fake()->paragraphs(2, true),
            "\n\n## La Solución\n\n" . fake()->paragraphs(4, true),
            "\n\n```php\n// Ejemplo de código\nfunction example(): void {\n    // implementación\n}\n```",
            "\n\n## Conclusión\n\n" . fake()->paragraphs(2, true),
        ];

        return implode('', $sections);
    }
}
