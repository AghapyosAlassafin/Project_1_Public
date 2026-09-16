<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use App\Services\NotificationService;

class VerificationController extends Controller
{
  /**
   * Verify registration code and activate account.
   */
  public function verifyRegistration(Request $request)
  {
    $data = $request->validate([
      'email' => 'required|email',
      'code' => 'required|string|size:6',
    ]);

    $record = EmailVerification::where('email', $data['email'])
      ->where('code', $data['code'])
      ->where('type', 'registration')
      ->where('used', false)
      ->where(function ($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
      })
      ->latest()
      ->first();

    if (!$record) {
      return response()->json(['message' => 'Invalid or expired code'], 422);
    }

    $record->used = true;
    $record->save();

    $user = User::where('email', $data['email'])->first();
    if ($user) {
      $user->email_verified_at = now();
      $user->save();
    }

    return response()->json(['message' => 'Account verified successfully']);
  }
  /**
   * Resend the email verification code to the user.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function resendCode(Request $request)
  {
    $data = $request->validate([
      'email' => 'required|email|exists:users,email',
    ]);

    $user = User::where('email', $data['email'])->first();

    // If the user's email is already verified, no need to resend.
    if ($user->email_verified_at !== null) {
      return response()->json(['message' => 'Email is already verified.'], 400);
    }

    // Delete any previous unused codes for this user (cleanup).
    EmailVerification::where('user_id', $user->id)
      ->where('type', 'registration')
      ->where('used', false)
      ->delete();

    // Generate a new 6-digit code.
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    EmailVerification::create([
      'email'      => $user->email,
      'user_id'    => $user->id,
      'code'       => $code,
      'type'       => 'registration',
      'expires_at' => now()->addMinutes(15),
    ]);

    // Send the verification email.
    Mail::to($user->email)->send(new VerificationCodeMail($code, 'registration'));

    return response()->json(['message' => 'Verification code resent to email.']);
  }
}
