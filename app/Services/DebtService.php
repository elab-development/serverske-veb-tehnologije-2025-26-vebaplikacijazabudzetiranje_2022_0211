<?php

namespace App\Services;

use App\Models\Group;

class DebtService
{
    public function calculateBalances(Group $group): array
    {
        $group->load([
            'users',
            'expenses.shares',
            'settlements'
        ]);

        $balances = [];

        foreach ($group->users as $user) {
            $paid = $group->expenses
                ->where('paid_by', $user->id)
                ->sum('amount');

            $owed = 0;

            foreach ($group->expenses as $expense) {
                $share = $expense->shares
                    ->where('user_id', $user->id)
                    ->first();

                if ($share) {
                    $owed += $share->amount_owed;
                }
            }

            $sentSettlements = $group->settlements
                ->where('from_user_id', $user->id)
                ->sum('amount');

            $receivedSettlements = $group->settlements
                ->where('to_user_id', $user->id)
                ->sum('amount');

            $balances[$user->id] = round(
                $paid
                    - $owed
                    + $sentSettlements
                    - $receivedSettlements,
                2
            );
        }

        return $balances;
    }

    public function calculateDebts(Group $group): array
    {
        $balances = $this->calculateBalances($group);

        $debtors = [];
        $creditors = [];

        foreach ($balances as $userId => $balance) {
            if ($balance < 0) {
                $debtors[$userId] = abs($balance);
            }

            if ($balance > 0) {
                $creditors[$userId] = $balance;
            }
        }

        $debts = [];

        foreach ($debtors as $debtorId => $debtAmount) {
            foreach ($creditors as $creditorId => $creditAmount) {
                if ($debtAmount <= 0) {
                    break;
                }

                if ($creditAmount <= 0) {
                    continue;
                }

                $amount = min($debtAmount, $creditAmount);

                $debts[] = [
                    'from_user_id' => $debtorId,
                    'to_user_id' => $creditorId,
                    'amount' => round($amount, 2),
                ];

                $debtAmount -= $amount;
                $creditors[$creditorId] -= $amount;
            }
        }

        return $debts;
    }
}
