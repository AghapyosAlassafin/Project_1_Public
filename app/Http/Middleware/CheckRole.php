<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
  /**
   * Handle an incoming request and verify user role.
   * Usage: middleware('role:admin') or middleware('role:admin|user')
   */
  public function handle(Request $request, Closure $next, string $roles)
  {
    $user = Auth::user();
    if (!$user) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    $allowed = explode('|', $roles);
    if (!in_array($user->role, $allowed, true)) {
      return response()->json(['message' => 'Forbidden'], 403);
    }

    return $next($request);
  }
}
