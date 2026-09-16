<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\EmailVerification;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use App\Services\GoogleAppsScriptMailer;


class RegisterController extends Controller
{
    /**
     * Register a new standard user and send verification code.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc|max:255',
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
            'phone' => 'nullable|string|regex:/^\+?[1-9]\d{7,14}$/',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // 1. Check for existing user email to handle incomplete registrations
        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            if ($existingUser->email_verified_at !== null) {
                return response()->json(['message' => 'Email already in use'], 422);
            }

            if ($existingUser->profile_image) {
                Storage::disk('public')->delete($existingUser->profile_image);
            }

            EmailVerification::where('user_id', $existingUser->id)->delete();
            $existingUser->delete();
        }

        // 2. Upload the image to Cloudinary if one is provided
        $imageUrl = null;
        if ($request->hasFile('profile_image')) {
            try {
                $imageUrl = Cloudinary::uploadApi()->upload(
                    $request->file('profile_image')->getRealPath()
                )['secure_url'];
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload profile image to Cloudinary: ' . $e->getMessage(),
                ], 500);
            }
        }

        // 3. Create the new user record
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'phone' => $data['phone'] ?? null,
            'profile_image' => $imageUrl,
            'role' => User::ROLE_USER,
        ]);

        // Generate 6-digit code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerification::create([
            'email' => $user->email,
            'user_id' => $user->id,
            'code' => $code,
            'type' => 'registration',
            'expires_at' => now()->addMinutes(15),
        ]);

        // Send email
        $html = (new VerificationCodeMail($code, 'registration'))->render();

        app(GoogleAppsScriptMailer::class)->send(
            $user->email,
            'Your Verification Code',
            $html
        );

        return response()->json(['message' => 'Verification code sent to email'], 201);
    }
}
