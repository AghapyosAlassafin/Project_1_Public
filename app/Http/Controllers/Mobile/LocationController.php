<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Favorite;
use Illuminate\Http\Request;

class LocationController extends Controller
{
  /**
   * Get details of a single location (including region and province).
   *
   * @param  int  $locationId
   * @return \Illuminate\Http\JsonResponse
   */
  public function show($locationId)
  {
    $location = Location::with('region.province')->findOrFail($locationId);

    return response()->json($location);
  }

  /**
   * Add a location to the authenticated user's favorites.
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function addFavorite(Request $request)
  {
    $data = $request->validate([
      'location_id' => 'required|integer|exists:locations,location_id',
    ]);

    $user = $request->user();

    // Avoid duplicate favorites
    $exists = Favorite::where('user_id', $user->id)
      ->where('location_id', $data['location_id'])
      ->exists();

    if ($exists) {
      return response()->json(['message' => 'Location already in favorites.'], 409);
    }

    Favorite::create([
      'user_id'     => $user->id,
      'location_id' => $data['location_id'],
    ]);

    return response()->json(['message' => 'Location added to favorites.'], 201);
  }

  /**
   * List all favorite locations for the authenticated user.
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function listFavorites(Request $request)
  {
    $user = $request->user();

    $favorites = $user->favoriteLocations()
      ->with('region.province')
      ->paginate(10);

    return response()->json($favorites);
  }

  /**
   * Remove a location from the authenticated user's favorites.
   *
   * @param  Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function removeFavorite(Request $request)
  {
    $data = $request->validate([
      'location_id' => 'required|integer|exists:locations,location_id',
    ]);

    $user = $request->user();

    $deleted = Favorite::where('user_id', $user->id)
      ->where('location_id', $data['location_id'])
      ->delete();

    if (!$deleted) {
      return response()->json(['message' => 'Favorite not found.'], 404);
    }

    return response()->json(['message' => 'Location removed from favorites.']);
  }

  /**
   * Get the "Location of the Week" – the location that appears most frequently
   * in trips starting within the current week.
   * Falls back to the most frequent location in future trips if no trips this week.
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function weeklyLocation()
  {
    $startOfWeek = now()->startOfWeek();
    $endOfWeek   = now()->endOfWeek();

    // Try to find the most frequent location in trips starting this week
    $mostFrequent = \App\Models\TripLocation::whereHas('trip', function ($q) use ($startOfWeek, $endOfWeek) {
      $q->where('start_date', '>=', $startOfWeek)
        ->where('start_date', '<=', $endOfWeek)
        ->where('status', 'published');
    })
      ->select('location_id')
      ->selectRaw('COUNT(*) as count')
      ->groupBy('location_id')
      ->orderByDesc('count')
      ->first();

    // Fallback: if no trips this week, get the most frequent location in all upcoming trips
    if (!$mostFrequent) {
      $mostFrequent = \App\Models\TripLocation::whereHas('trip', function ($q) {
        $q->where('start_date', '>=', now())
          ->where('status', 'published');
      })
        ->select('location_id')
        ->selectRaw('COUNT(*) as count')
        ->groupBy('location_id')
        ->orderByDesc('count')
        ->first();
    }

    // If still no trips exist, pick a random location (or return 404)
    if (!$mostFrequent) {
      $location = Location::inRandomOrder()->first();
      if (!$location) {
        return response()->json(['message' => 'No locations found.'], 404);
      }
    } else {
      $location = Location::with('region.province')->find($mostFrequent->location_id);
    }

    // Format response
    return response()->json([
      'location_id'   => $location->location_id,
      'name'          => $location->name,
      'description'   => $location->description,
      'image'         => $location->image,
      'latitude'      => $location->latitude,
      'longitude'     => $location->longitude,
      'province_name' => $location->region->province->name ?? null,
      'region_name'   => $location->region->name ?? null,
    ]);
  }
}
