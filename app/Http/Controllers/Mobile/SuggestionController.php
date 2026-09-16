<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
  /**
   * Submit a new suggestion from the authenticated user.
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function store(Request $request)
  {
    $data = $request->validate([
      'description' => 'required|string|max:2000',
    ]);

    $suggestion = Suggestion::create([
      'user_id'     => $request->user()->id,
      'description' => $data['description'],
    ]);

    return response()->json([
      'message'    => 'Suggestion submitted.',
      'suggestion' => $suggestion,
    ], 201);
  }
}
