<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario admin fijo para pruebas
        User::factory()->admin()->create([
            'name'     => 'Admin Blog',
            'username' => 'admin',
            'email'    => 'admin@blog.test',
        ]);

        // Un editor
        User::factory()->editor()->create([
            'name'     => 'Editor Principal',
            'username' => 'editor',
            'email'    => 'editor@blog.test',
        ]);

        // 5 autores
        User::factory(5)->author()->create();

        // 50 lectores (con mezcla de activos e inactivos)
        User::factory(45)->create();
        User::factory(5)->inactive()->create();

        $this->command->info('Users seeded: ' . User::count());
    }
}
