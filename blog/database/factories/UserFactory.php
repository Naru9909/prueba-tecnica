<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * PREGUNTA: ¿Qué es una Factory en Laravel?
 * ¿Cuál es la diferencia entre una Factory y un Seeder?
 * ¿Cuándo usarías cada uno?
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'               => fake()->name(),
            'username'           => fake()->unique()->userName(),
            'email'              => fake()->unique()->safeEmail(),
            'email_verified_at'  => now(),
            'password'           => static::$password ??= Hash::make('password'),
            'avatar'             => null,
            'bio'                => fake()->optional(0.7)->paragraph(),
            'role'               => 'reader',
            'is_active'          => true,
            'website'            => fake()->optional(0.4)->url(),
            'twitter_handle'     => fake()->optional(0.5)->userName(),
            'remember_token'     => Str::random(10),
        ];
    }

    /**
     * PREGUNTA: ¿Qué son los "states" en una Factory?
     * ¿Cómo se usan en un test? Ejemplo: User::factory()->admin()->create()
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function editor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'editor',
        ]);
    }

    public function author(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'author',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
