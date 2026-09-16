<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Trip;
use App\Models\UserTrip;
use App\Models\Location;
use App\Models\Region;
use App\Models\Province;
use App\Models\Suggestion;

class AdminController extends Controller
{
    private function checkAdmin()
    {
        $auth = Auth::user();
        return ($auth && $auth->role === 'admin') ? $auth : false;
    }

    // ============================================================
    // USERS MANAGEMENT
    // ============================================================

    public function listUsers()
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $users = User::where('role', 'user')
            ->latest()
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'profile_image' => $user->profile_image,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    public function showUser($id)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:50',
            'profile_image' => 'nullable|string|max:255',
        ]);

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $user->fresh()
        ]);
    }

    public function deleteUser($id)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->apiTokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    // ============================================================
    // MODERATORS MANAGEMENT
    // ============================================================

    public function listModerators()
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $moderators = User::whereIn('role', [
            'tourist-site-moderator',
            'party-trip-moderator'
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'moderators' => $moderators
        ]);
    }

    public function createModerator(Request $request)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
            'profile_image' => 'nullable|string|max:255',
            'role' => 'required|in:tourist-site-moderator,party-trip-moderator'
        ]);

        $data['password'] = Hash::make($data['password']);

        $moderator = User::forceCreate(array_merge($data, [
            'email_verified_at' => now(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Moderator created successfully',
            'moderator' => $moderator
        ], 201);
    }

    public function updateModerator(Request $request, $id)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $moderator = User::whereIn('role', [
            'tourist-site-moderator',
            'party-trip-moderator'
        ])->find($id);

        if (!$moderator) {
            return response()->json(['success' => false, 'message' => 'Moderator not found'], 404);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:50',
            'profile_image' => 'nullable|string|max:255',
            'role' => 'nullable|in:tourist-site-moderator,party-trip-moderator,user',
        ]);

        $moderator->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Moderator updated successfully',
            'moderator' => $moderator->fresh()
        ]);
    }

    public function deleteModerator($id)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $moderator = User::whereIn('role', [
            'tourist-site-moderator',
            'party-trip-moderator'
        ])->find($id);

        if (!$moderator) {
            return response()->json(['success' => false, 'message' => 'Moderator not found'], 404);
        }

        $moderator->delete();

        return response()->json([
            'success' => true,
            'message' => 'Moderator deleted successfully'
        ]);
    }

    public function fullData(Request $request)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = $request->get('per_page', 20);

        return response()->json([
            'success' => true,
            'data' => [
                'users'         => User::where('role', 'user')->latest()->paginate($perPage),
                'moderators'    => User::whereIn('role', [
                    'tourist-site-moderator',
                    'party-trip-moderator'
                ])->latest()->paginate($perPage),
                'trips'         => Trip::with('moderator')->latest()->paginate($perPage),
                'bookings'      => UserTrip::with(['user', 'trip'])->latest()->paginate($perPage),
                'locations'     => Location::with('region.province')->latest()->paginate($perPage),
                'regions'       => Region::with('province')->latest()->paginate($perPage),
                'provinces'     => Province::latest()->paginate($perPage),
                'suggestions'   => Suggestion::with('user')->latest()->paginate($perPage),
            ]
        ]);
    }
}
