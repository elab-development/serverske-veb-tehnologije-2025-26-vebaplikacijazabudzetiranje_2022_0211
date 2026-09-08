<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use \App\Models\Group;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::with(['group', 'category', 'payer']);

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $allowedSorts = ['amount', 'payment_date', 'created_at'];

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);

        $expenses = $query->paginate(10);

        return ExpenseResource::collection($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'payment_date' => 'required|date',
        ]);

        $group = Group::findOrFail($validated['group_id']);

        if (!$group->users()->where('users.id', $request->user()->id)->exists()) {
            return response()->json([
                'message' => 'Morate biti član grupe da biste dodali trošak.'
            ], 403);
        }

        $expense = Expense::create([
            'group_id' => $validated['group_id'],
            'category_id' => $validated['category_id'],
            'paid_by' => $request->user()->id,
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'payment_date' => $validated['payment_date'],
        ]);

        $members = $group->users()->get();

        $shareAmount = round(
            $expense->amount / $members->count(),
            2
        );

        foreach ($members as $member) {
            $expense->shares()->create([
                'user_id' => $member->id,
                'amount_owed' => $shareAmount,
                'is_paid' => $member->id === $request->user()->id,
            ]);
        }

        $expense->load(['group', 'category', 'payer', 'shares']);

        return response()->json([
            'message' => 'Trošak je uspešno kreiran.',
            'data' => new ExpenseResource($expense),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        $expense->load(['group', 'category', 'payer', 'shares']);

        return new ExpenseResource($expense);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        if (
            $expense->paid_by !== $request->user()->id &&
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za izmenu ovog troška.'
            ], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'amount' => 'sometimes|numeric|min:0.01',
            'description' => 'sometimes|string|max:255',
            'payment_date' => 'sometimes|date',
        ]);

        $expense->update($validated);

        if (array_key_exists('amount', $validated)) {
            $members = $expense->group->users;

            $memberCount = $members->count();
            $shareAmount = round($expense->amount / $memberCount, 2);
            $remainingAmount = $expense->amount;

            foreach ($members as $index => $member) {
                if ($index === $memberCount - 1) {
                    $amountOwed = $remainingAmount;
                } else {
                    $amountOwed = $shareAmount;
                    $remainingAmount -= $shareAmount;
                }

                $expense->shares()
                    ->where('user_id', $member->id)
                    ->update([
                        'amount_owed' => $amountOwed,
                    ]);
            }
        }

        $expense->load(['group', 'category', 'payer', 'shares']);

        return response()->json([
            'message' => 'Trošak je uspešno izmenjen.',
            'data' => new ExpenseResource($expense),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Expense $expense)
    {
        if (
            $expense->paid_by !== $request->user()->id &&
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za brisanje ovog troška.'
            ], 403);
        }

        $expense->delete();

        return response()->json([
            'message' => 'Trošak je uspešno obrisan.'
        ]);
    }
}
