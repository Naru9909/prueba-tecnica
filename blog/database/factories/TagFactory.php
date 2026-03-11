<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    private static array $techTags = [
        'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'Vue.js', 'React',
        'Docker', 'Kubernetes', 'AWS', 'PostgreSQL', 'Redis', 'GraphQL',
        'REST API', 'TDD', 'CI/CD', 'Microservices', 'DDD', 'SOLID',
        'Clean Code', 'Refactoring', 'Performance', 'Security', 'Nginx',
        'Linux', 'Git', 'Python', 'Go', 'Node.js', 'Tailwind', 'MySQL',
    ];

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(self::$techTags);

        return [
            'name'  => $name,
            'slug'  => Str::slug($name),
            'color' => fake()->hexColor(),
        ];
    }
}
