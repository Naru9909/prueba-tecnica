<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    private static array $techCategories = [
        'Backend'           => '#3B82F6',
        'Frontend'          => '#F59E0B',
        'DevOps'            => '#10B981',
        'Seguridad'         => '#EF4444',
        'Machine Learning'  => '#8B5CF6',
        'Bases de Datos'    => '#F97316',
        'Arquitectura'      => '#06B6D4',
        'Open Source'       => '#84CC16',
        'Cloud'             => '#6366F1',
        'Mobile'            => '#EC4899',
    ];

    public function definition(): array
    {
        $name  = fake()->unique()->randomElement(array_keys(self::$techCategories));
        $color = self::$techCategories[$name];

        return [
            'name'        => $name,
            'slug'        => Str::slug($name),
            'description' => fake()->sentence(),
            'color'       => $color,
            'icon'        => null,
            'parent_id'   => null,
            'sort_order'  => fake()->numberBetween(0, 20),
            'is_active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
