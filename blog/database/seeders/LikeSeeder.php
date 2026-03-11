<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class LikeSeeder extends Seeder
{
    public function run(): void
    {
        $userIds        = User::pluck('id');
        $publishedPosts = Post::published()->pluck('id');
        $approvedComments = Comment::approved()->pluck('id');

        // Likes en posts — asegurando unique (user_id, likeable_id, likeable_type)
        $usedPostCombos = [];
        $likesCreated   = 0;

        foreach ($publishedPosts->random(min(60, $publishedPosts->count())) as $postId) {
            $nLikes = fake()->numberBetween(0, 30);
            $shuffledUsers = $userIds->shuffle()->take($nLikes);

            foreach ($shuffledUsers as $userId) {
                $key = "{$userId}_{$postId}";
                if (!isset($usedPostCombos[$key])) {
                    $usedPostCombos[$key] = true;
                    Like::create([
                        'user_id'       => $userId,
                        'likeable_id'   => $postId,
                        'likeable_type' => \App\Models\Post::class,
                    ]);
                    $likesCreated++;
                }
            }
        }

        // Likes en comentarios
        $usedCommentCombos = [];
        foreach ($approvedComments->random(min(50, $approvedComments->count())) as $commentId) {
            $nLikes = fake()->numberBetween(0, 10);
            $shuffledUsers = $userIds->shuffle()->take($nLikes);

            foreach ($shuffledUsers as $userId) {
                $key = "{$userId}_{$commentId}";
                if (!isset($usedCommentCombos[$key])) {
                    $usedCommentCombos[$key] = true;
                    Like::create([
                        'user_id'       => $userId,
                        'likeable_id'   => $commentId,
                        'likeable_type' => \App\Models\Comment::class,
                    ]);
                    $likesCreated++;
                }
            }
        }

        $this->command->info("Likes seeded: {$likesCreated}");
    }
}
