<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $publishedPosts = Post::published()->pluck('id');
        $userIds        = User::pluck('id');

        // Comentarios raíz en posts publicados
        $rootComments = Comment::factory(300)
            ->sequence(fn ($seq) => [
                'post_id' => $publishedPosts->random(),
                'user_id' => $userIds->random(),
            ])
            ->create();

        // Respuestas (replies) a algunos comentarios raíz
        $rootComments->random(80)->each(function (Comment $parent) use ($userIds) {
            $replyCount = fake()->numberBetween(1, 5);
            for ($i = 0; $i < $replyCount; $i++) {
                Comment::factory()
                    ->reply($parent)
                    ->create(['user_id' => $userIds->random()]);
            }
        });

        // Algunos comentarios pendientes de moderación
        Comment::factory(20)
            ->pending()
            ->sequence(fn ($seq) => [
                'post_id' => $publishedPosts->random(),
                'user_id' => $userIds->random(),
            ])
            ->create();

        $this->command->info('Comments seeded: ' . Comment::count());
    }
}
