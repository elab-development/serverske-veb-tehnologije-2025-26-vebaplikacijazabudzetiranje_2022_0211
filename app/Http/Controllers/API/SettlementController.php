<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettlementResource;
use App\Models\Group;
use App\Models\Settlement;
use Illuminate\Http\Request;
use App\Services\DebtService;

class SettlementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Settlement::with(['group', 'fromUser', 'toUser']);

        if ($request->user()->role !== 'admin') {
            $query->whereHas('group.users', function ($q) use ($request) {
                $q->where('users.id', $request->user()->id);
            });
        }

        return SettlementResource::collection(
            $query->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, DebtService $debtService)
    {
        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'to_user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $group = Group::findOrFail($validated['group_id']);

        if (!$group->users()->where('users.id', $request->user()->id)->exists()) {
            return response()->json([
                'message' => 'Morate biti član grupe.'
            ], 403);
        }

        if (!$group->users()->where('users.id', $validated['to_user_id'])->exists()) {
            return response()->json([
                'message' => 'Korisnik kome plaćate nije član ove grupe.'
            ], 422);
        }

        if ($request->user()->id === (int) $validated['to_user_id']) {
            return response()->json([
                'message' => 'Ne možete izvršiti settlement prema samom sebi.'
            ], 422);
        }
        $group->load([
            'users',
            'expenses.shares',
            'settlements'
        ]);


        $debts = $debtService->calculateDebts($group);

        $currentDebt = collect($debts)->first(function ($debt) use ($request, $validated) {
            return
                $debt['from_user_id'] === $request->user()->id &&
                $debt['to_user_id'] === (int) $validated['to_user_id'];
        });

        if (!$currentDebt) {
            return response()->json([
                'message' => 'Nemate dug prema ovom korisniku.'
            ], 422);
        }

        if ($validated['amount'] > $currentDebt['amount']) {
            return response()->json([
                'message' => 'Iznos settlement-a je veći od trenutnog duga.',
                'current_debt' => $currentDebt['amount'],
            ], 422);
        }


        $settlement = Settlement::create([
            'group_id' => $validated['group_id'],
            'from_user_id' => $request->user()->id,
            'to_user_id' => $validated['to_user_id'],
            'amount' => $validated['amount'],
            'settled_at' => now(),
        ]);

        return response()->json([
            'message' => 'Dug je uspešno evidentiran kao plaćen.',
            'data' => new SettlementResource($settlement),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Settlement $settlement)
    {
        $settlement->load(['group.users', 'fromUser', 'toUser']);

        $isMember = $settlement->group->users
            ->contains('id', $request->user()->id);

        if (!$isMember && $request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Nemate pristup ovom settlement-u.'
            ], 403);
        }

        return new SettlementResource($settlement);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Settlement $settlement) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Settlement $settlement)
    {
        if (
            $settlement->from_user_id !== $request->user()->id &&
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za brisanje ovog settlement-a.'
            ], 403);
        }

        $settlement->delete();

        return response()->json([
            'message' => 'Settlement je uspešno obrisan.'
        ]);
    }
}
