<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return GroupResource::collection(Group::with(['creator', 'users'])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $group->users()->attach($request->user()->id);

        return response()->json([
            'message' => 'Grupa je uspešno kreirana.',
            'data' => new GroupResource($group),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Group $group)
    {
        $group->load(['creator', 'users']);

        return new GroupResource($group);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Group $group)
    {
        if (
            $group->created_by !== $request->user()->id &&
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za izmenu ove grupe.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->update($validated);

        return response()->json([
            'message' => 'Grupa je uspešno izmenjena.',
            'data' => new GroupResource($group),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Group $group)
    {
        if (
            $group->created_by !== $request->user()->id &&
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za brisanje ove grupe.'
            ], 403);
        }

        $group->delete();

        return response()->json([
            'message' => 'Grupa je uspešno obrisana.'
        ]);
    }

    public function members(Group $group)
    {
        $group->load('users');

        return response()->json([
            'group_id' => $group->id,
            'group_name' => $group->name,
            'members' => $group->users,
        ]);
    }

    public function addMember(Request $request, Group $group)
    {
        if (
            $group->created_by !== $request->user()->id &&
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za dodavanje članova.'
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        if ($group->users()->where('users.id', $validated['user_id'])->exists()) {
            return response()->json([
                'message' => 'Korisnik je već član ove grupe.'
            ], 409);
        }

        $group->users()->attach($validated['user_id']);

        return response()->json([
            'message' => 'Korisnik je uspešno dodat u grupu.'
        ], 201);
    }

    public function removeMember(Request $request, Group $group, $user)
    {
        if (
            $group->created_by !== $request->user()->id &&
            $request->user()->role !== 'admin'
        ) {
            return response()->json([
                'message' => 'Nemate dozvolu za uklanjanje članova.'
            ], 403);
        }

        if ((int) $user === (int) $group->created_by) {
            return response()->json([
                'message' => 'Kreator grupe ne može biti uklonjen.'
            ], 400);
        }

        if (!$group->users()->where('users.id', $user)->exists()) {
            return response()->json([
                'message' => 'Korisnik nije član ove grupe.'
            ], 404);
        }

        $group->users()->detach($user);

        return response()->json([
            'message' => 'Korisnik je uspešno uklonjen iz grupe.'
        ]);
    }

    public function balances(Group $group)
    {
        $group->load([
            'users',
            'expenses.shares',
        ]);

        $balances = [];

        foreach ($group->users as $user) {
            $paid = $group->expenses
                ->where('paid_by', $user->id)
                ->sum('amount');

            $owed = 0;

            foreach ($group->expenses as $expense) {
                $share = $expense->shares
                    ->firstWhere('user_id', $user->id);

                if ($share) {
                    $owed += $share->amount_owed;
                }
            }

            $balance = round($paid - $owed, 2);

            $balances[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'paid' => round($paid, 2),
                'owed' => round($owed, 2),
                'balance' => $balance,
                'status' => $balance > 0
                    ? 'potražuje'
                    : ($balance < 0 ? 'duguje' : 'izmiren'),
            ];
        }

        return response()->json([
            'group_id' => $group->id,
            'group_name' => $group->name,
            'balances' => $balances,
        ]);
    }
}
