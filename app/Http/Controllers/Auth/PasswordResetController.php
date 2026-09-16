<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailVerification;
use App\Models\User;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Mail;
use App\Services\GoogleAppsScriptMailer;


class PasswordResetController extends Controller
{
    /**
     * Request a password reset code to be sent to the user's email.
     */
    public function requestReset(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'If the email exists, a reset code will be sent.']);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerification::create([
            'email' => $user->email,
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(15),
        ]);

        $html = (new VerificationCodeMail($code, 'password_reset'))->render();

        app(GoogleAppsScriptMailer::class)->send(
            $user->email,
            'Your Password Reset Code',
            $html
        );
        return response()->json(['message' => 'If the email exists, a reset code will be sent.']);
    }

    /**
     * Verify reset code and set new password.
     */
    public function verifyAndReset(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            //should be at least 8 characters, contain uppercase, number and special character
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[!@#$%^&*]/',
            ],
            'password_confirmation' => 'required|string',
        ]);

        $record = EmailVerification::where('email', $data['email'])
            ->where('code', $data['code'])
            ->where('type', 'password_reset')
            ->where('used', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired code'], 422);
        }

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->password = $data['password'];
        $user->save();

        $record->used = true;
        $record->save();

        return response()->json(['message' => 'Password has been reset']);
    }
}
