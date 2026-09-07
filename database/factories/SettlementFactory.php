<?php

namespace Database\Factories;

use App\Models\Settlement;
use \App\Models\Group;
use \App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Settlement>
 */
class SettlementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::query()->inRandomOrder()->value('id'),
            'from_user_id' => User::query()->inRandomOrder()->value('id'),
            'to_user_id' => User::query()->inRandomOrder()->value('id'),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'settled_at' => fake()->dateTime(),
        ];
    }
}
