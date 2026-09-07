<?php

namespace Database\Factories;

use App\Models\ExpenseShare;
use \App\Models\Expense;

use \App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseShare>
 */
class ExpenseShareFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'expense_id' => Expense::query()->inRandomOrder()->value('id'),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'amount_owed' => fake()->randomFloat(2, 100, 10000),
            'is_paid' => fake()->boolean(),
        ];
    }
}
