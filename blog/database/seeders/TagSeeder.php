<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'PHP',           'color' => '#7C3AED'],
            ['name' => 'Laravel',       'color' => '#F05340'],
            ['name' => 'JavaScript',    'color' => '#F7DF1E'],
            ['name' => 'TypeScript',    'color' => '#3178C6'],
            ['name' => 'Vue.js',        'color' => '#41B883'],
            ['name' => 'React',         'color' => '#61DAFB'],
            ['name' => 'Docker',        'color' => '#2496ED'],
            ['name' => 'Kubernetes',    'color' => '#326CE5'],
            ['name' => 'AWS',           'color' => '#FF9900'],
            ['name' => 'PostgreSQL',    'color' => '#336791'],
            ['name' => 'Redis',         'color' => '#DC382D'],
            ['name' => 'GraphQL',       'color' => '#E10098'],
            ['name' => 'REST API',      'color' => '#06B6D4'],
            ['name' => 'TDD',           'color' => '#10B981'],
            ['name' => 'CI/CD',         'color' => '#8B5CF6'],
            ['name' => 'Microservices', 'color' => '#F97316'],
            ['name' => 'DDD',           'color' => '#EF4444'],
            ['name' => 'SOLID',         'color' => '#6366F1'],
            ['name' => 'Clean Code',    'color' => '#14B8A6'],
            ['name' => 'Performance',   'color' => '#F59E0B'],
            ['name' => 'Security',      'color' => '#EF4444'],
            ['name' => 'Nginx',         'color' => '#009900'],
            ['name' => 'Linux',         'color' => '#FCC624'],
            ['name' => 'Git',           'color' => '#F05032'],
            ['name' => 'MySQL',         'color' => '#4479A1'],
        ];

        foreach ($tags as $tag) {
            Tag::create([
                'name'  => $tag['name'],
                'slug'  => Str::slug($tag['name']),
                'color' => $tag['color'],
            ]);
        }

        $this->command->info('Tags seeded: ' . Tag::count());
    }
}
