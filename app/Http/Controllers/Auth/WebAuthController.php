<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class WebAuthController extends Controller
{
  /**
   * Authenticate a web user (admin and moderators only).
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

    // Allow only admin and moderators to login via web
    $allowedRoles = [User::ROLE_ADMIN, User::ROLE_TOURIST_MOD, User::ROLE_PARTY_MOD];
    if (!in_array($user->role, $allowedRoles)) {
      return response()->json([
        'message' => 'This account cannot login via web platform. Please use the mobile application.'
      ], 403);
    }

    $token = $user->createApiToken('web-auth-token');

    return response()->json(['token' => $token]);
  }

  /**
   * Logout web user (invalidate current token).
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
