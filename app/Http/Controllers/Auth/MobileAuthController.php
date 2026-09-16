<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MobileAuthController extends Controller
{
  /**
   * Authenticate a mobile user (regular users only).
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function login(Request $request)
  {
    $data = $request->validate([
      'email' => 'required|email',
      'password' => 'required|string',
    ]);

    $user = User::where('email', $data['email'])->first();

    // Verify credentials
    if (!$user || !Hash::check($data['password'], $user->password)) {
      return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // Check email verification
    if (!$user->email_verified_at) {
      return response()->json(['message' => 'Email not verified'], 403);
    }

    // Allow only regular users to login via mobile
    if ($user->role !== User::ROLE_USER) {
      return response()->json([
        'message' => 'This account cannot login via mobile application. Please use the web platform.'
      ], 403);
    }

    $token = $user->createApiToken('mobile-auth-token');

    return response()->json(['token' => $token]);
  }

  /**
   * Logout mobile user (invalidate current token).
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function logout(Request $request)
  {
    $header = $request->header('Authorization', '');

    if (!str_starts_with($header, 'Bearer ')) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    $tokenString = substr($header, 7);
    [$id] = array_pad(explode('|', $tokenString, 2), 1, null);

    if ($id) {
      $record = $request->user()->apiTokens()->where('id', $id)->first();
      if ($record) {
        $record->delete();
      }
    }

    return response()->json(['message' => 'Logged out successfully']);
  }
}
