<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Group;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 20; $i++) {

            $group = Group::with('users')->inRandomOrder()->first();

            $payer = $group->users->random();

            Expense::create([
                'group_id' => $group->id,
                'category_id' => Category::inRandomOrder()->value('id'),
                'paid_by' => $payer->id,
                'amount' => fake()->randomFloat(2, 500, 20000),
                'description' => fake()->sentence(),
                'payment_date' => fake()->date(),
            ]);
        }
    }
}
