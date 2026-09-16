<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Suggestion;

class SuggestionController extends Controller
{
    /**
     * Submit a suggestion, usually from the mobile client.
     * This method is not restricted to a specific web role because users call it directly.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'description' => 'required|string|max:1000|min:10',
        ]);

        $suggestion = Suggestion::create([
            'user_id' => $user->id,
            'description' => $data['description'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Suggestion submitted successfully',
            'suggestion' => $suggestion
        ], 201);
    }

    public function index()
    {
        $auth = Auth::user();

        if (!$auth || $auth->role !== 'tourist-site-moderator') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $suggestions = Suggestion::with('user')
            ->latest()
            ->get()
            ->map(function ($suggestion) {
                return [
                    'suggestion_id' => $suggestion->suggestion_id,
                    'user_id' => $suggestion->user_id,
                    'user_name' => $suggestion->user->name ?? 'Deleted User',
                    'user_email' => $suggestion->user->email ?? 'N/A',
                    'description' => $suggestion->description,
                    'created_at' => $suggestion->created_at,
                    'updated_at' => $suggestion->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    public function destroy($id)
    {
        $auth = Auth::user();

        if (!$auth || $auth->role !== 'tourist-site-moderator') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $suggestion = Suggestion::find($id);

        if (!$suggestion) {
            return response()->json(['message' => 'Suggestion not found'], 404);
        }

        $suggestion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Suggestion deleted successfully'
        ]);
    }
}
