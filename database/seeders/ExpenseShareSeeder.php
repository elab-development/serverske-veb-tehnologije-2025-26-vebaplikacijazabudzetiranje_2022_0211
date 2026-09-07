<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseShare;
use Illuminate\Database\Seeder;

class ExpenseShareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expenses = Expense::with('group.users')->get();

        foreach ($expenses as $expense) {

            $members = $expense->group->users;

            if ($members->isEmpty()) {
                continue;
            }

            $memberCount = $members->count();

            $shareAmount = round(
                $expense->amount / $memberCount,
                2
            );

            $remainingAmount = $expense->amount;

            foreach ($members as $index => $member) {

                if ($index === $memberCount - 1) {
                    $amountOwed = $remainingAmount;
                } else {
                    $amountOwed = $shareAmount;
                    $remainingAmount -= $shareAmount;
                }

                ExpenseShare::create([
                    'expense_id' => $expense->id,
                    'user_id' => $member->id,
                    'amount_owed' => $amountOwed,
                    'is_paid' => $member->id === $expense->paid_by,
                ]);
            }
        }
    }
}
