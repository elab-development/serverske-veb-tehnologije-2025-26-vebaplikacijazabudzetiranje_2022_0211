<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Models\Expense;

class ExchangeRateController extends Controller
{
    public function getRate(Request $request)
    {
        $validated = $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        $from = strtoupper($validated['from']);
        $to = strtoupper($validated['to']);

        if ($from === $to) {
            return response()->json([
                'from' => $from,
                'to' => $to,
                'rate' => 1,
            ]);
        }

        $cacheKey = "exchange_rate_{$from}_{$to}";

        $data = Cache::remember(
            $cacheKey,
            now()->addHour(),
            function () use ($from, $to) {

                $response = Http::withoutVerifying()->get(
                    "https://api.frankfurter.dev/v2/rate/{$from}/{$to}"
                );

                if ($response->failed()) {
                    return null;
                }

                return $response->json();
            }
        );

        if (!$data || !isset($data['rate'])) {
            return response()->json([
                'message' => 'Nije moguće učitati kurs valuta.'
            ], 502);
        }

        return response()->json([
            'from' => $data['base'],
            'to' => $data['quote'],
            'rate' => $data['rate'],
            'date' => $data['date'],
        ]);
    }


    public function convertExpense(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'currency' => 'required|string|size:3',
        ]);

        $from = 'RSD';
        $to = strtoupper($validated['currency']);

        if ($from === $to) {
            return response()->json([
                'expense_id' => $expense->id,
                'original_amount' => (float) $expense->amount,
                'original_currency' => $from,
                'converted_amount' => (float) $expense->amount,
                'converted_currency' => $to,
                'rate' => 1,
            ]);
        }

        $cacheKey = "exchange_rate_{$from}_{$to}";

        $data = Cache::remember(
            $cacheKey,
            now()->addHour(),
            function () use ($from, $to) {

                $response = Http::withoutVerifying()->get(
                    "https://api.frankfurter.dev/v2/rate/{$from}/{$to}"
                );

                if ($response->failed()) {
                    return null;
                }

                return $response->json();
            }
        );

        if (!$data || !isset($data['rate'])) {
            return response()->json([
                'message' => 'Nije moguće izvršiti konverziju valute.'
            ], 502);
        }

        $convertedAmount = round(
            $expense->amount * $data['rate'],
            2
        );

        return response()->json([
            'expense_id' => $expense->id,
            'description' => $expense->description,

            'original_amount' => (float) $expense->amount,
            'original_currency' => $from,

            'converted_amount' => $convertedAmount,
            'converted_currency' => $to,

            'rate' => $data['rate'],
            'rate_date' => $data['date'] ?? null,
        ]);
    }
}
