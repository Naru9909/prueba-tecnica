<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PREGUNTA: ¿Cuál es la diferencia entre DatabaseSeeder y un Seeder específico?
 * ¿Por qué se usa $this->call() en lugar de correr todo aquí?
 * ¿Qué ventaja tiene el trait WithoutModelEvents en seeders?
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Orden importa: respetar las FK constraints
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            LikeSeeder::class,
        ]);
    }
}
