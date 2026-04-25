<?php

declare(strict_types=1);

namespace Domain\Identity\Database\Factories;

use Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Application User factory.
 *
 * Produces `Domain\Identity\Models\User` instances with Iraqi-flavored
 * defaults: a ULID `huwiya_id`, an Iraqi mobile number, a safe email,
 * and sampled locale / zoneinfo / theme values.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'huwiya_id' => (string) Str::ulid(),
            'name' => fake()->name(),
            'phone' => '+964'.fake()->numerify('7#########'),
            'email' => fake()->unique()->safeEmail(),
            'locale' => fake()->randomElement(['en', 'ar', '']),
            'zoneinfo' => fake()->randomElement(['Asia/Baghdad', 'UTC', '']),
            'theme' => fake()->randomElement(['light', 'dark', '']),
        ];
    }

    public function withoutHuwiya(): static
    {
        return $this->state(fn () => ['huwiya_id' => null]);
    }
}
