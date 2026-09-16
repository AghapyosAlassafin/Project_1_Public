<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Trip;
use App\Models\UserTrip;
use App\Models\Location;
use App\Models\Suggestion;

class ReportController extends Controller
{
    private function checkAdmin()
    {
        $auth = Auth::user();
        return ($auth && $auth->role === 'admin') ? $auth : false;
    }

    // ============================================================
    // GENERAL REPORTS (Admin only)
    // ============================================================

    public function index()
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'success' => true,
            'reports' => [
                'users_count' => User::count(),
                'moderators_count' => User::whereIn('role', [
                    'tourist-site-moderator',
                    'party-trip-moderator'
                ])->count(),
                'trips_count' => Trip::count(),
                'bookings_count' => UserTrip::count(),
                'locations_count' => Location::count(),
                'suggestions_count' => Suggestion::count(),
                'total_revenue' => UserTrip::sum('total_price'),
                'top_trip' => Trip::withCount('users')->orderBy('users_count', 'desc')->first(),
            ]
        ]);
    }

    // ============================================================
    // REPORT FOR A SPECIFIC TRIP (Admin only)
    // ============================================================

    public function show($id)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip = Trip::with('moderator')->find($id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $bookings = UserTrip::where('trip_id', $id)->get();
        $totalBookings = $bookings->count();
        $totalRevenue = $bookings->sum('total_price');
        $averageRating = $bookings->whereNotNull('rate')->avg('rate') ?? 0;

        return response()->json([
            'success' => true,
            'trip_report' => [
                'trip' => [
                    'id' => $trip->trip_id,
                    'name' => $trip->name,
                    'status' => $trip->status,
                    'start_date' => $trip->start_date,
                    'end_date' => $trip->end_date,
                    'capacity' => $trip->capacity,
                    'price' => $trip->price,
                    'discount' => $trip->discount,
                ],
                'moderator' => $trip->moderator ? [
                    'id' => $trip->moderator->id,
                    'name' => $trip->moderator->name,
                    'email' => $trip->moderator->email,
                ] : null,
                'statistics' => [
                    'total_bookings' => $totalBookings,
                    'total_revenue' => $totalRevenue,
                    'average_rating' => round($averageRating, 2),
                    'ratings_count' => $bookings->whereNotNull('rate')->count(),
                ],
                'bookings' => $bookings->map(function ($booking) {
                    return [
                        'user_trip_id' => $booking->user_trip_id,
                        'user_id' => $booking->user_id,
                        'user_name' => $booking->user->name ?? 'Deleted User',
                        'user_email' => $booking->user->email ?? 'N/A',
                        'people_number' => $booking->people_number,
                        'total_price' => $booking->total_price,
                        'rate' => $booking->rate,
                        'created_at' => $booking->created_at,
                    ];
                }),
            ]
        ]);
    }

    // ============================================================
    // COMPLETED TRIPS REPORT (Admin only)
    // ============================================================

    public function completedTrips()
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $completedTrips = Trip::where('status', 'completed')
            ->with('moderator')
            ->get()
            ->map(function ($trip) {
                $bookings = UserTrip::where('trip_id', $trip->trip_id)->get();

                return [
                    'trip' => [
                        'id' => $trip->trip_id,
                        'name' => $trip->name,
                        'start_date' => $trip->start_date,
                        'end_date' => $trip->end_date,
                    ],
                    'moderator' => $trip->moderator ? [
                        'id' => $trip->moderator->id,
                        'name' => $trip->moderator->name,
                    ] : null,
                    'statistics' => [
                        'total_bookings' => $bookings->count(),
                        'total_revenue' => $bookings->sum('total_price'),
                        'average_rating' => round($bookings->whereNotNull('rate')->avg('rate') ?? 0, 2),
                        'ratings_count' => $bookings->whereNotNull('rate')->count(),
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'completed_trips' => $completedTrips,
            'total_completed_trips' => $completedTrips->count(),
            'total_revenue_all' => $completedTrips->sum('statistics.total_revenue'),
        ]);
    }

    // ============================================================
    // MODERATOR REPORT (Admin only)
    // ============================================================

    public function moderatorReport($moderatorId)
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $moderator = User::find($moderatorId);

        if (!$moderator) {
            return response()->json(['message' => 'Moderator not found'], 404);
        }

        if (!in_array($moderator->role, ['party-trip-moderator', 'admin'])) {
            return response()->json(['message' => 'User is not a trip moderator'], 400);
        }

        $trips = Trip::where('moderated_by', $moderatorId)->get();
        $tripIds = $trips->pluck('trip_id');
        $bookings = UserTrip::whereIn('trip_id', $tripIds)->get();

        $totalTrips = $trips->count();
        $completedTrips = $trips->where('status', 'completed')->count();
        $ongoingTrips = $trips->where('status', 'ongoing')->count();
        $cancelledTrips = $trips->where('status', 'cancelled')->count();
        $publishedTrips = $trips->where('status', 'published')->count();

        $totalBookings = $bookings->count();
        $totalRevenue = $bookings->sum('total_price');
        $averageRating = $bookings->whereNotNull('rate')->avg('rate') ?? 0;
        $totalVisitors = $bookings->sum('people_number');

        $tripRatings = $trips->map(function ($trip) {
            $tripBookings = UserTrip::where('trip_id', $trip->trip_id)->get();
            return [
                'trip_id' => $trip->trip_id,
                'trip_name' => $trip->name,
                'average_rating' => round($tripBookings->whereNotNull('rate')->avg('rate') ?? 0, 2),
                'ratings_count' => $tripBookings->whereNotNull('rate')->count(),
                'bookings_count' => $tripBookings->count(),
                'revenue' => $tripBookings->sum('total_price'),
            ];
        });

        return response()->json([
            'success' => true,
            'moderator' => [
                'id' => $moderator->id,
                'name' => $moderator->name,
                'email' => $moderator->email,
                'role' => $moderator->role,
            ],
            'statistics' => [
                'total_trips' => $totalTrips,
                'completed_trips' => $completedTrips,
                'ongoing_trips' => $ongoingTrips,
                'cancelled_trips' => $cancelledTrips,
                'published_trips' => $publishedTrips,
                'total_bookings' => $totalBookings,
                'total_revenue' => $totalRevenue,
                'total_visitors' => $totalVisitors,
                'average_rating' => round($averageRating, 2),
            ],
            'trip_ratings' => $tripRatings,
        ]);
    }

    // ============================================================
    // ALL MODERATORS REPORT (Admin only)
    // ============================================================

    public function allModeratorsReport()
    {
        if (!$this->checkAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $moderators = User::whereIn('role', ['party-trip-moderator', 'tourist-site-moderator'])->get();

        $reports = $moderators->map(function ($moderator) {
            if ($moderator->role === 'party-trip-moderator') {
                $trips = Trip::where('moderated_by', $moderator->id)->get();
                $tripIds = $trips->pluck('trip_id');
                $bookings = UserTrip::whereIn('trip_id', $tripIds)->get();

                return [
                    'moderator' => [
                        'id' => $moderator->id,
                        'name' => $moderator->name,
                        'email' => $moderator->email,
                        'role' => $moderator->role,
                    ],
                    'statistics' => [
                        'total_trips' => $trips->count(),
                        'completed_trips' => $trips->where('status', 'completed')->count(),
                        'total_bookings' => $bookings->count(),
                        'total_revenue' => $bookings->sum('total_price'),
                        'total_visitors' => $bookings->sum('people_number'),
                        'average_rating' => round($bookings->whereNotNull('rate')->avg('rate') ?? 0, 2),
                    ],
                ];
            }

            return [
                'moderator' => [
                    'id' => $moderator->id,
                    'name' => $moderator->name,
                    'email' => $moderator->email,
                    'role' => $moderator->role,
                ],
                'statistics' => [
                    'total_sites' => Location::count(),
                    'message' => 'Site moderator - no trip statistics',
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'moderators_report' => $reports,
        ]);
    }
}
