<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\UserTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\NotificationService;

class TripController extends Controller
{
  /**
   * List available trips (published, not full, upcoming).
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function index(Request $request)
  {
    $data = $request->validate([
      'start_date'   => 'nullable|date',
      'end_date'     => 'nullable|date|after_or_equal:start_date',
      'price_min'    => 'nullable|numeric|min:0',
      'price_max'    => 'nullable|numeric|min:0',
      'province_ids'   => 'nullable|array',
      'province_ids.*' => 'integer|exists:provinces,province_id',
      'region_ids'     => 'nullable|array',
      'region_ids.*'   => 'integer|exists:regions,region_id',
      'location_ids'   => 'nullable|array',
      'location_ids.*' => 'integer|exists:locations,location_id',
    ]);

    $query = Trip::where('status', 'published')
      ->whereColumn('travelers_number', '<', 'capacity')
      ->where('start_date', '>', now())
      ->orderBy('start_date');

    // Date filters
    $start = $request->input('start_date');
    $end   = $request->input('end_date');
    if ($start && $end) {
      $query->where('start_date', '<', $end)
        ->where('end_date', '>', $start);
    } elseif ($start) {
      $query->where('start_date', '>=', $start);
    } elseif ($end) {
      $query->where('end_date', '<=', $end);
    }

    // Price filters
    if ($request->has('price_min')) {
      $query->where('price', '>=', $request->price_min);
    }
    if ($request->has('price_max')) {
      $query->where('price', '<=', $request->price_max);
    }

    // Multiple location IDs (trip must pass through ALL specified locations)
    if ($request->has('location_ids')) {
      foreach ($request->location_ids as $locationId) {
        $query->whereHas('locations', function ($q) use ($locationId) {
          $q->where('locations.location_id', $locationId);
        });
      }
    }

    // Multiple region IDs (trip must have locations in ALL specified regions)
    if ($request->has('region_ids')) {
      foreach ($request->region_ids as $regionId) {
        $query->whereHas('locations', function ($q) use ($regionId) {
          $q->whereHas('region', function ($q2) use ($regionId) {
            $q2->where('regions.region_id', $regionId);
          });
        });
      }
    }

    // Multiple province IDs (trip must have locations in ALL specified provinces)
    if ($request->has('province_ids')) {
      foreach ($request->province_ids as $provinceId) {
        $query->whereHas('locations', function ($q) use ($provinceId) {
          $q->whereHas('region.province', function ($q2) use ($provinceId) {
            $q2->where('provinces.province_id', $provinceId);
          });
        });
      }
    }

    return response()->json($query->paginate(10));
  }

  /**
   * Get the authenticated user's booked trips (upcoming / ongoing).
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function userTrips(Request $request)
  {
    $user = $request->user();

    // Only trips that haven't ended yet
    $trips = $user->trips()
      ->where('end_date', '>=', now())
      ->orderBy('start_date')
      ->paginate(10);

    return response()->json($trips);
  }

  /**
   * Get the authenticated user's past (finished) trips.
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function userPastTrips(Request $request)
  {
    $user = $request->user();

    $trips = $user->trips()
      ->where('end_date', '<', now())
      ->orderBy('start_date', 'desc')
      ->paginate(10);

    return response()->json($trips);
  }

  /**
   * Book a trip for the authenticated user.
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function book(Request $request)
  {
    $data = $request->validate([
      'trip_id'       => 'required|integer|exists:trips,trip_id',
      'people_number' => 'nullable|integer|min:1|max:10',
    ]);

    $user = $request->user();
    $trip = Trip::findOrFail($data['trip_id']);

    // Validate trip is available for booking
    if ($trip->status !== 'published') {
      return response()->json(['message' => 'This trip is not available.'], 400);
    }
    if ($trip->start_date <= now()) {
      return response()->json(['message' => 'The trip has already started.'], 400);
    }
    if ($trip->travelers_number >= $trip->capacity) {
      return response()->json(['message' => 'The trip is fully booked.'], 400);
    }

    // Prevent duplicate booking
    if (UserTrip::where('user_id', $user->id)->where('trip_id', $trip->trip_id)->exists()) {
      return response()->json(['message' => 'You have already booked this trip.'], 409);
    }

    $people = $data['people_number'] ?? 1;

    // Check remaining capacity
    $remaining = $trip->capacity - $trip->travelers_number;
    if ($people > $remaining) {
      return response()->json([
        'message' => "Not enough capacity. Only {$remaining} spot(s) left."
      ], 400);
    }

    // Execute booking
    $booking = DB::transaction(function () use ($trip, $user, $people) {
      $booking = UserTrip::create([
        'user_id'       => $user->id,
        'trip_id'       => $trip->trip_id,
        'payment_code'  => 'PAY-DUMMY-' . Str::upper(Str::random(6)),
        'people_number' => $people,
      ]);

      $trip->increment('travelers_number', $people);
      return $booking;
    });

    app(NotificationService::class)->sendToUser(
      $user,
      'Booking Confirmed',
      "Your booking for {$trip->name} has been confirmed.",
      'booking',
      ['trip_id' => $trip->trip_id]
    );

    return response()->json([
      'message'          => 'Trip booked successfully.',
      'payment_code'     => $booking->payment_code,
      'price_per_person' => $trip->discounted_price,
      'total_price'      => $booking->total_price,
    ], 201);
  }

  /**
   * Cancel a booking (only if the trip hasn't started yet).
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function cancel(Request $request)
  {
    $data = $request->validate([
      'trip_id' => 'required|integer|exists:trips,trip_id',
    ]);

    $user = $request->user();
    $booking = UserTrip::where('user_id', $user->id)
      ->where('trip_id', $data['trip_id'])
      ->first();

    if (!$booking) {
      return response()->json(['message' => 'Booking not found.'], 404);
    }

    $trip = $booking->trip;

    if ($trip->start_date <= now()) {
      return response()->json(['message' => 'Cannot cancel after the trip has started.'], 400);
    }

    DB::transaction(function () use ($booking, $trip) {
      $people = $booking->people_number;
      $booking->delete();
      $trip->decrement('travelers_number', $people);
    });

    app(NotificationService::class)->sendToUser(
      $user,
      'Booking Cancelled',
      "Your booking for '{$trip->name}' has been cancelled.",
      'booking',
      ['trip_id' => $trip->trip_id]
    );

    return response()->json(['message' => 'Booking cancelled successfully.']);
  }

  /**
   * Rate a finished trip (user must have participated and trip must be finished).
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function rate(Request $request)
  {
    $data = $request->validate([
      'trip_id' => 'required|integer|exists:trips,trip_id',
      'rate'    => 'required|integer|between:1,5',
    ]);

    $user = $request->user();

    // Find the user's booking for this trip
    $booking = UserTrip::where('user_id', $user->id)
      ->where('trip_id', $data['trip_id'])
      ->first();

    if (!$booking) {
      return response()->json(['message' => 'You are not a participant of this trip.'], 403);
    }

    $trip = $booking->trip;

    // Ensure the trip has ended
    if ($trip->end_date > now()) {
      return response()->json(['message' => 'You can only rate a trip after it has ended.'], 400);
    }

    // Prevent multiple ratings
    if ($booking->rate !== null) {
      return response()->json(['message' => 'You have already rated this trip.'], 409);
    }

    DB::transaction(function () use ($booking, $trip, $data) {
      // Save the rating
      $booking->rate = $data['rate'];
      $booking->save();

      // Recalculate average rating and number of ratings for the trip
      // Only rows WHERE rate IS NOT NULL are considered (actual evaluators)
      $stats = UserTrip::where('trip_id', $trip->trip_id)
        ->whereNotNull('rate')
        ->selectRaw('AVG(rate) as avg, COUNT(*) as count')
        ->first();

      $trip->ratings_avg     = round($stats->avg, 2);   // Average of actual ratings
      $trip->ratings_numbers = $stats->count;           // Number of users who rated (not total bookings)
      $trip->save();
    });

    return response()->json(['message' => 'Rating submitted successfully.']);
  }

  /**
   * Show trip details with locations ordered by sequence.
   *
   * @param  int  $tripId
   * @return \Illuminate\Http\JsonResponse
   */
  public function show($tripId)
  {
    $trip = Trip::with(['locations' => function ($q) {
      $q->orderBy('sequence_order');
    }])
      ->findOrFail($tripId);

    // Hide pivot data from locations (optional)
    $trip->locations->makeHidden('pivot');

    return response()->json($trip);
  }
}
