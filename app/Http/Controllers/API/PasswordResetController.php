<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $status = Password::sendResetLink([
            'email' => $request->email,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Link za resetovanje lozinke je poslat.'
            ]);
        }

        return response()->json([
            'message' => 'Nije moguće poslati link za resetovanje lozinke.'
        ], 500);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Lozinka je uspešno promenjena.'
            ]);
        }

        return response()->json([
            'message' => 'Token nije validan ili je istekao.'
        ], 400);
    }
}
