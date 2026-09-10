<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\Request;
use \App\Models\Group;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::with(['group', 'category', 'payer']);

        if ($request->user()->role !== 'admin') {
            $query->whereHas('group.users', function ($q) use ($request) {
                $q->where('users.id', $request->user()->id);
            });
        }

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
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $group = Group::findOrFail($validated['group_id']);

        if (!$group->users()->where('users.id', $request->user()->id)->exists()) {
            return response()->json([
                'message' => 'Morate biti član grupe da biste dodali trošak.'
            ], 403);
        }

        $expense = DB::transaction(function () use ($request, $validated, $group) {

            $receiptPath = null;

            if ($request->hasFile('receipt')) {
                $receiptPath = $request
                    ->file('receipt')
                    ->store('receipts', 'public');
            }

            $expense = Expense::create([
                'group_id' => $validated['group_id'],
                'category_id' => $validated['category_id'],
                'paid_by' => $request->user()->id,
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'payment_date' => $validated['payment_date'],
                'receipt_path' => $receiptPath,
            ]);

            $members = $group->users()->get();

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

                $expense->shares()->create([
                    'user_id' => $member->id,
                    'amount_owed' => $amountOwed,
                    'is_paid' => $member->id === $request->user()->id,
                ]);
            }

            return $expense;
        });

        $expense->load(['group', 'category', 'payer', 'shares']);

        return response()->json([
            'message' => 'Trošak je uspešno kreiran.',
            'data' => new ExpenseResource($expense),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Expense $expense)
    {
        $expense->load(['group.users', 'category', 'payer', 'shares']);

        $isMember = $expense->group->users
            ->contains('id', $request->user()->id);

        if (!$isMember && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Nemate pristup ovom trošku.'
            ], 403);
        }

        return new ExpenseResource($expense);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $isMember = $expense->group->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Nemate pristup ovom trošku.'
            ], 403);
        }

        if (
            $expense->paid_by !== $request->user()->id &&
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za izmenu ovog troška.'
            ], 403);
        }

        $validated = $request->validate([
            'group_id' => 'sometimes|required|exists:groups,id',
            'category_id' => 'sometimes|required|exists:categories,id',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'description' => 'sometimes|required|string|max:255',
            'payment_date' => 'sometimes|required|date',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($request->hasFile('receipt')) {

            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }

            $validated['receipt_path'] = $request
                ->file('receipt')
                ->store('receipts', 'public');
        }

        unset($validated['receipt']);

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

        $isMember = $expense->group->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        if (!$isMember && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Nemate pristup ovom trošku.'
            ], 403);
        }

        if (
            $expense->paid_by !== $request->user()->id &&
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za brisanje ovog troška.'
            ], 403);
        }

        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return response()->json([
            'message' => 'Trošak je uspešno obrisan.'
        ]);
    }

    public function exportCsv()
    {
        $expenses = Expense::with(['group', 'category', 'payer'])->get();

        $fileName = 'expenses_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        $callback = function () use ($expenses) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Group',
                'Category',
                'Payer',
                'Amount',
                'Description',
                'Payment Date'
            ]);

            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->id,
                    $expense->group?->name,
                    $expense->category?->name,
                    $expense->payer?->name,
                    $expense->amount,
                    $expense->description,
                    $expense->payment_date,
                ]);
            }

            fclose($file);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }
}
