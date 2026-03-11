<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Backend',          'color' => '#3B82F6', 'description' => 'Desarrollo del lado del servidor, APIs y arquitectura.'],
            ['name' => 'Frontend',         'color' => '#F59E0B', 'description' => 'Interfaces de usuario, CSS, JavaScript y frameworks.'],
            ['name' => 'DevOps',           'color' => '#10B981', 'description' => 'Infraestructura, CI/CD, contenedores y nube.'],
            ['name' => 'Seguridad',        'color' => '#EF4444', 'description' => 'Seguridad en aplicaciones, vulnerabilidades y buenas prácticas.'],
            ['name' => 'Machine Learning', 'color' => '#8B5CF6', 'description' => 'Inteligencia artificial, modelos y datos.'],
            ['name' => 'Bases de Datos',   'color' => '#F97316', 'description' => 'SQL, NoSQL, optimización y diseño de esquemas.'],
            ['name' => 'Arquitectura',     'color' => '#06B6D4', 'description' => 'Patrones de diseño, DDD, microservicios y más.'],
            ['name' => 'Open Source',      'color' => '#84CC16', 'description' => 'Proyectos, contribuciones y comunidad open source.'],
            ['name' => 'Cloud',            'color' => '#6366F1', 'description' => 'AWS, GCP, Azure y servicios cloud.'],
            ['name' => 'Mobile',           'color' => '#EC4899', 'description' => 'Desarrollo para iOS, Android y React Native.'],
        ];

        foreach ($categories as $index => $cat) {
            Category::create([
                'name'        => $cat['name'],
                'slug'        => Str::slug($cat['name']),
                'description' => $cat['description'],
                'color'       => $cat['color'],
                'sort_order'  => $index,
                'is_active'   => true,
            ]);
        }

        // Subcategorías de Backend
        $backend = Category::where('slug', 'backend')->first();
        $subCats = ['Laravel', 'Symfony', 'Node.js', 'Go', 'Python/Django'];
        foreach ($subCats as $i => $name) {
            Category::create([
                'name'       => $name,
                'slug'       => Str::slug($name),
                'parent_id'  => $backend->id,
                'sort_order' => $i,
                'is_active'  => true,
            ]);
        }

        $this->command->info('Categories seeded: ' . Category::count());
    }
}
