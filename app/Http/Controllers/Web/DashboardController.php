<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Trip;
use App\Models\UserTrip;
use App\Models\Location;
use App\Models\Region;
use App\Models\Province;
use App\Models\Suggestion;

class DashboardController extends Controller
{
    // ============================================================
    // ADMIN AND MODERATOR STATS
    // ============================================================
    public function stats()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        switch ($user->role) {
            case 'admin':
                return $this->adminStats();
            case 'tourist-site-moderator':
                return $this->locationModStats();
            case 'party-trip-moderator':
                return $this->tripModStats();
            default:
                return response()->json(['message' => 'Unauthorized role'], 403);
        }
    }

    public function overview()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        switch ($user->role) {
            case 'admin':
                return $this->adminOverview();
            case 'tourist-site-moderator':
                return $this->locationModOverview();
            case 'party-trip-moderator':
                return $this->tripModOverview();
            default:
                return response()->json(['message' => 'Unauthorized role'], 403);
        }
    }

    // ADMIN STATS
    private function adminStats()
    {
        return response()->json([
            'success' => true,
            'role' => 'admin',
            'stats' => [
                'users_count'       => User::count(),
                'moderators_count'  => User::whereIn('role', ['tourist-site-moderator', 'party-trip-moderator'])->count(),
                'trips_count'       => Trip::count(),
                'bookings_count'    => UserTrip::count(),
                'locations_count'   => Location::count(),
                'regions_count'     => Region::count(),
                'provinces_count'   => Province::count(),
                'suggestions_count' => Suggestion::count(),
            ]
        ]);
    }

    private function adminOverview()
    {
        return response()->json([
            'success' => true,
            'role' => 'admin',
            'overview' => [
                'users'         => User::latest()->get(),
                'moderators'    => User::whereIn('role', ['tourist-site-moderator', 'party-trip-moderator'])->latest()->get(),
                'trips'         => Trip::with('moderator')->latest()->get(),
                'bookings'      => UserTrip::with(['user', 'trip'])->latest()->get(),
                'locations'     => Location::with('region.province')->latest()->get(),
                'regions'       => Region::with('province')->latest()->get(),
                'provinces'     => Province::latest()->get(),
            ]
        ]);
    }

    // TOURIST SITE MODERATOR STATS
    private function locationModStats()
    {
        return response()->json([
            'success' => true,
            'role' => 'tourist-site-moderator',
            'stats' => [
                'locations_count'   => Location::count(),
                'regions_count'     => Region::count(),
                'provinces_count'   => Province::count(),
                'suggestions_count' => Suggestion::count(),
            ]
        ]);
    }

    private function locationModOverview()
    {
        return response()->json([
            'success' => true,
            'role' => 'tourist-site-moderator',
            'overview' => [
                'locations'     => Location::with('region.province')->latest()->get(),
                'regions'       => Region::with('province')->latest()->get(),
                'provinces'     => Province::latest()->get(),
                'suggestions'   => Suggestion::with('user')->latest()->get(),
            ]
        ]);
    }

    // PARTY TRIP MODERATOR STATS
    private function tripModStats()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'role' => 'party-trip-moderator',
            'stats' => [
                'trips_count'       => Trip::where('moderated_by', $user->id)->count(),
                'ongoing_trips'     => Trip::where('moderated_by', $user->id)->where('status', 'ongoing')->count(),
                'completed_trips'   => Trip::where('moderated_by', $user->id)->where('status', 'completed')->count(),
                'cancelled_trips'   => Trip::where('moderated_by', $user->id)->where('status', 'cancelled')->count(),
                'bookings_count'    => UserTrip::whereHas('trip', function ($q) use ($user) {
                                            $q->where('moderated_by', $user->id);
                                        })->count(),
            ]
        ]);
    }

    private function tripModOverview()
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'role' => 'party-trip-moderator',
            'overview' => [
                'trips'         => Trip::where('moderated_by', $user->id)->with('locations')->latest()->get(),
                'bookings'      => UserTrip::whereHas('trip', function ($q) use ($user) {
                                            $q->where('moderated_by', $user->id);
                                        })->with(['user', 'trip'])->latest()->get(),
            ]
        ]);
    }
}
