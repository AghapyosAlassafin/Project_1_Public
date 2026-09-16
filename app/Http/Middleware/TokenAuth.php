<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\ApiToken;

class TokenAuth
{
  /**
   * Handle an incoming request.
   */
  public function handle(Request $request, Closure $next)
  {
    $header = $request->header('Authorization', '');
    if (!str_starts_with($header, 'Bearer ')) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    $tokenString = substr($header, 7);

    // Expect format: {id}|{plain}
    [$id, $plain] = array_pad(explode('|', $tokenString, 2), 2, null);

    if (empty($id) || empty($plain)) {
      return response()->json(['message' => 'Invalid token format'], 401);
    }

    $record = ApiToken::find($id);
    if (!$record) {
      return response()->json(['message' => 'Invalid token'], 401);
    }

    if (!Hash::check($plain, $record->token_hash)) {
      return response()->json(['message' => 'Invalid token credentials'], 401);
    }

    // Set last used and authenticate the user for this request
    $record->last_used_at = now();
    $record->saveQuietly();

    $user = $record->user;
    Auth::setUser($user);

    return $next($request);
  }
}
