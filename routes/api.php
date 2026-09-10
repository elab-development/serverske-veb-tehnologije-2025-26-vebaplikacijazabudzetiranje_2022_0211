<?php

use App\Http\Controllers\API\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\SettlementController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ExchangeRateController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);

Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::get('/reset-password/{token}', function ($token) {
    return response()->json([
        'token' => $token,
        'email' => request('email'),
        'message' => 'Koristite ovaj token za resetovanje lozinke.'
    ]);
})->name('password.reset');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/test', function () {
        return response()->json([
            'message' => 'Admin pristup je uspešan.'
        ]);
    });

    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
});


Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::put('/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);
    Route::get('/groups/{group}/members', [GroupController::class, 'members']);
    Route::post('/groups/{group}/members', [GroupController::class, 'addMember']);
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember']);
    Route::get('/groups/{group}/balances', [GroupController::class, 'balances']);
    Route::get('/groups/{group}/debts', [GroupController::class, 'debts']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/expenses/export/csv', [ExpenseController::class, 'exportCsv']);

    Route::apiResource('expenses', ExpenseController::class);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settlements', [SettlementController::class, 'index']);
    Route::post('/settlements', [SettlementController::class, 'store']);
    Route::get('/settlements/{settlement}', [SettlementController::class, 'show']);
    Route::delete('/settlements/{settlement}', [SettlementController::class, 'destroy']);
});


Route::get('/exchange-rate', [ExchangeRateController::class, 'getRate']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/expenses/{expense}/convert', [ExchangeRateController::class, 'convertExpense']);
});

Route::get('/holidays', [HolidayController::class, 'index']);
