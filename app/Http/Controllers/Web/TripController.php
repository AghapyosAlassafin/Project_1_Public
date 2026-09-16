<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\UserTrip;
use App\Models\Location;
use App\Services\NotificationService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class TripController extends Controller
{
    private function checkModerator()
    {
        $auth = Auth::user();
        if (!$auth) {
            return null;
        }

        if (in_array($auth->role, ['admin', 'party-trip-moderator'])) {
            return $auth;
        }

        return null;
    }

    private function checkPartyModerator()
    {
        $auth = Auth::user();
        if (!$auth || $auth->role !== 'party-trip-moderator') {
            return null;
        }
        return $auth;
    }

    public function index()
    {
        $auth = $this->checkModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Trip::with('moderator');

        if ($auth->role === 'party-trip-moderator') {
            $query->where('moderated_by', $auth->id);
        }

        $trips = $query->latest()->get();

        return response()->json([
            'success' => true,
            'trips' => $trips
        ]);
    }

    public function show($id)
    {
        $auth = $this->checkModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = Trip::with(['moderator', 'locations']);

        if ($auth->role === 'party-trip-moderator') {
            $query->where('moderated_by', $auth->id);
        }

        $trip = $query->find($id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        return response()->json([
            'success' => true,
            'trip' => $trip
        ]);
    }

    public function store(Request $request)
    {
        $auth = $this->checkPartyModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'image' => 'nullable',
            'image_url' => 'nullable|url|max:2048',
            'url' => 'nullable|url|max:2048',
            'discount' => 'nullable|numeric|min:0|max:100',
        ]);

        if (!$request->hasFile('image') && empty($data['image'])) {
            $data['image'] = $data['image_url'] ?? $data['url'] ?? null;
        }
        unset($data['image_url']);
        unset($data['url']);

        try {
            if ($request->hasFile('image')) {
                $request->validate(['image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120']);
                $data['image'] = Cloudinary::uploadApi()->upload(
                    $request->file('image')->getRealPath()
                )['secure_url'];
            } elseif (!empty($data['image']) && !filter_var($data['image'], FILTER_VALIDATE_URL)) {
                return response()->json(['success' => false, 'message' => 'The image must be a valid URL or image file.'], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload trip image to Cloudinary: ' . $e->getMessage(),
            ], 500);
        }

        $data['moderated_by'] = $auth->id;
        $data['status'] = Trip::STATUS_DRAFT;
        $data['travelers_number'] = 0;

        $trip = Trip::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Trip created successfully',
            'trip' => $trip->load('moderator')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $auth = $this->checkPartyModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip = Trip::where('moderated_by', $auth->id)->find($id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'price' => 'nullable|numeric|min:0',
            'capacity' => 'nullable|integer|min:1',
            'image' => 'sometimes|nullable',
            'image_url' => 'sometimes|nullable|url|max:2048',
            'url' => 'sometimes|nullable|url|max:2048',
            'discount' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:draft,published,ongoing,completed,cancelled',
        ]);

        if (!$request->hasFile('image') && empty($data['image'])) {
            $data['image'] = $data['image_url'] ?? $data['url'] ?? null;
        }
        unset($data['image_url']);
        unset($data['url']);

        try {
            if ($request->hasFile('image')) {
                $request->validate(['image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120']);
                $data['image'] = Cloudinary::uploadApi()->upload(
                    $request->file('image')->getRealPath()
                )['secure_url'];
            } elseif (array_key_exists('image', $data) && !empty($data['image']) && !filter_var($data['image'], FILTER_VALIDATE_URL)) {
                return response()->json(['success' => false, 'message' => 'The image must be a valid URL or image file.'], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload trip image to Cloudinary: ' . $e->getMessage(),
            ], 500);
        }

        $trip->update($data);

        app(NotificationService::class)->notifyTripUpdated($trip);

        return response()->json([
            'success' => true,
            'message' => 'Trip updated successfully',
            'trip' => $trip->fresh()
        ]);
    }

    public function destroy($id)
    {
        $auth = $this->checkPartyModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip = Trip::where('moderated_by', $auth->id)->find($id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $bookingsCount = UserTrip::where('trip_id', $id)->count();
        if ($bookingsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete trip with existing bookings'
            ], 422);
        }

        $trip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trip deleted successfully'
        ]);
    }

    public function cancel($id)
    {
        return $this->changeStatus($id, Trip::STATUS_CANCELLED);
    }

    public function confirm($id)
    {
        return $this->changeStatus($id, Trip::STATUS_PUBLISHED);
    }

    private function changeStatus($id, $status)
    {
        $auth = $this->checkPartyModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip = Trip::where('moderated_by', $auth->id)->find($id);

        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $oldStatus = $trip->status;
        $trip->update(['status' => $status]);

        if ($status === Trip::STATUS_PUBLISHED && $oldStatus !== Trip::STATUS_PUBLISHED) {
            app(NotificationService::class)->notifyFavoriteLocationUsers($trip);
        }

        return response()->json([
            'success' => true,
            'message' => "Trip status changed to {$status}",
            'trip' => $trip->fresh()
        ]);
    }

    public function addLocation(Request $request, $tripId)
    {
        $auth = $this->checkPartyModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip = Trip::where('moderated_by', $auth->id)->find($tripId);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $data = $request->validate([
            'location_id' => 'required|exists:locations,location_id',
            'sequence_order' => 'required|integer|min:0',
        ]);

        $exists = TripLocation::where('trip_id', $tripId)
            ->where('location_id', $data['location_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Location already added to this trip'], 422);
        }

        $tripLocation = TripLocation::create([
            'trip_id' => $tripId,
            'location_id' => $data['location_id'],
            'sequence_order' => $data['sequence_order']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location added to trip successfully',
            'trip_location' => $tripLocation
        ], 201);
    }

    public function removeLocation($tripId, $locationId)
    {
        $auth = $this->checkPartyModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip = Trip::where('moderated_by', $auth->id)->find($tripId);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $tripLocation = TripLocation::where('trip_id', $tripId)
            ->where('location_id', $locationId)
            ->first();

        if (!$tripLocation) {
            return response()->json(['message' => 'Location not found in this trip'], 404);
        }

        $tripLocation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location removed from trip successfully'
        ]);
    }

    public function bookings($tripId)
    {
        $auth = $this->checkPartyModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $trip = Trip::where('moderated_by', $auth->id)->find($tripId);
        if (!$trip) {
            return response()->json(['message' => 'Trip not found'], 404);
        }

        $bookings = UserTrip::where('trip_id', $tripId)
            ->with('user')
            ->latest()
            ->get()
            ->map(function ($booking) {
                return [
                    'user_trip_id' => $booking->user_trip_id,
                    'user_id' => $booking->user_id,
                    'user_name' => $booking->user->name ?? 'Deleted User',
                    'user_email' => $booking->user->email ?? 'N/A',
                    'user_phone' => $booking->user->phone ?? 'N/A',
                    'people_number' => $booking->people_number,
                    'total_price' => $booking->total_price,
                    'payment_code' => $booking->payment_code,
                    'rate' => $booking->rate,
                    'created_at' => $booking->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'trip' => [
                'id' => $trip->trip_id,
                'name' => $trip->name,
                'status' => $trip->status,
            ],
            'bookings' => $bookings,
            'total_bookings' => $bookings->count(),
            'total_revenue' => $bookings->sum('total_price')
        ]);
    }

    // ============================================================
    // UPLOAD TRIP IMAGE
    // Same logic as LocationController::uploadImage(), restricted to
    // party-trip-moderator and stored in the separate "trips" directory.
    // ============================================================
    public function uploadImage(Request $request)
    {
        $auth = $this->checkPartyModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            $imageUrl = Cloudinary::uploadApi()->upload(
                $request->file('image')->getRealPath()
            )['secure_url'];

            return response()->json([
                'success' => true,
                'image' => $imageUrl,
                'url' => $imageUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500);
        }
    }
}
