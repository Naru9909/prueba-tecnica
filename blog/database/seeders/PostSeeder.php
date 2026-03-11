<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * PREGUNTA: ¿Por qué es importante el orden de los seeders?
 * ¿Qué problema causaría si corriéramos PostSeeder antes de UserSeeder?
 */
class PostSeeder extends Seeder
{
    public function run(): void
    {
        $authors    = User::whereIn('role', ['author', 'editor', 'admin'])->pluck('id');
        $categories = Category::whereNull('parent_id')->pluck('id');
        $allTags    = Tag::pluck('id')->toArray();

        // 10 posts featured publicados
        Post::factory(10)
            ->featured()
            ->withViews(1000, 50000)
            ->withMeta()
            ->sequence(fn ($seq) => [
                'user_id'     => $authors->random(),
                'category_id' => $categories->random(),
            ])
            ->create()
            ->each(function (Post $post) use ($allTags) {
                // PREGUNTA: ¿Qué hace sync() en una relación BelongsToMany?
                $post->tags()->sync(
                    collect($allTags)->random(fake()->numberBetween(2, 5))->toArray()
                );
            });

        // 80 posts publicados normales
        Post::factory(80)
            ->published()
            ->withViews(10, 5000)
            ->sequence(fn ($seq) => [
                'user_id'     => $authors->random(),
                'category_id' => $categories->random(),
            ])
            ->create()
            ->each(function (Post $post) use ($allTags) {
                $post->tags()->sync(
                    collect($allTags)->random(fake()->numberBetween(1, 4))->toArray()
                );
            });

        // 20 borradores
        Post::factory(20)
            ->sequence(fn ($seq) => [
                'user_id'     => $authors->random(),
                'category_id' => $categories->random(),
            ])
            ->create();

        // 5 archivados
        Post::factory(5)
            ->archived()
            ->sequence(fn ($seq) => [
                'user_id'     => $authors->random(),
                'category_id' => $categories->random(),
            ])
            ->create();

        $this->command->info('Posts seeded: ' . Post::count());
    }
}
