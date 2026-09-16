<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Login and return a token.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Email not verified'], 403);
        }

        $token = $user->createApiToken('auth-token');

        return response()->json(['token' => $token]);
    }

    /**
     * Logout current token (invalidate).
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

        return response()->json(['message' => 'Logged out']);
    }
}
