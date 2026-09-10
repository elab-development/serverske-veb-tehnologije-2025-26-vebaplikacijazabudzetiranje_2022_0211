<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'country' => 'required|string|size:2',
        ]);

        $year = $validated['year'];
        $country = strtoupper($validated['country']);

        $response = Http::withoutVerifying()->get(
            "https://date.nager.at/api/v3/PublicHolidays/{$year}/{$country}"
        );

        if ($response->failed()) {
            return response()->json([
                'message' => 'Nije moguće učitati državne praznike.'
            ], 502);
        }

        return response()->json([
            'year' => $year,
            'country' => $country,
            'holidays' => $response->json(),
        ]);
    }
}
