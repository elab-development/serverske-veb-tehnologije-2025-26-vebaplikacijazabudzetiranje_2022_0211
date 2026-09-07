<?php

namespace Database\Factories;

use App\Models\Expense;
use \App\Models\Group;
use \App\Models\Category;
use \App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
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
            'category_id' => Category::query()->inRandomOrder()->value('id'),
            'paid_by' => User::query()->inRandomOrder()->value('id'),
            'amount' => fake()->randomFloat(2, 500, 20000),
            'description' => fake()->sentence(),
            'payment_date' => fake()->date(),
        ];
    }
}
