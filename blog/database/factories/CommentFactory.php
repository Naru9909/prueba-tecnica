<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id'    => Post::factory(),
            'user_id'    => User::factory(),
            'parent_id'  => null,
            'body'       => fake()->paragraph(fake()->numberBetween(1, 4)),
            'status'     => 'approved',
            'is_edited'  => false,
            'edited_at'  => null,
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function spam(): static
    {
        return $this->state(['status' => 'spam']);
    }

    public function reply(Comment $parent): static
    {
        return $this->state([
            'parent_id'  => $parent->id,
            'post_id'    => $parent->post_id,
        ]);
    }

    public function edited(): static
    {
        return $this->state([
            'is_edited' => true,
            'edited_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
