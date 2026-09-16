<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Location;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class LocationController extends Controller
{
    // ============================================================
    // Helpers
    // ============================================================
    private function checkTouristModerator()
    {
        $auth = Auth::user();
        if (!$auth || $auth->role !== 'tourist-site-moderator') {
            return null;
        }
        return $auth;
    }

    private function canRead()
    {
        $auth = Auth::user();
        if (!$auth) {
            return null;
        }
        if (!in_array($auth->role, ['admin', 'tourist-site-moderator', 'party-trip-moderator'])) {
            return null;
        }
        return $auth;
    }

    // ============================================================
    // LIST LOCATIONS
    // ============================================================
    public function index()
    {
        $auth = $this->canRead();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $locations = Location::with('region.province')->latest()->get();
            return response()->json([
                'success' => true,
                'locations' => $locations
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // SHOW LOCATION
    // ============================================================
    public function show($id)
    {
        $auth = $this->canRead();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $location = Location::with('region.province')->find($id);

            if (!$location) {
                return response()->json(['message' => 'Location not found'], 404);
            }

            return response()->json([
                'success' => true,
                'location' => $location
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // CREATE LOCATION
    // ============================================================
    public function store(Request $request)
    {
        $auth = $this->checkTouristModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $data = $request->validate([
                'region_id' => 'required|exists:regions,region_id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'image' => 'nullable',
                'image_url' => 'nullable|url|max:2048',
                'url' => 'nullable|url|max:2048'
            ]);

            if (!$request->hasFile('image') && empty($data['image'])) {
                $data['image'] = $data['image_url'] ?? $data['url'] ?? null;
            }
            unset($data['image_url']);
            unset($data['url']);

            if ($request->hasFile('image')) {
                $request->validate(['image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120']);
                $data['image'] = Cloudinary::uploadApi()->upload(
                    $request->file('image')->getRealPath()
                )['secure_url'];
            } elseif (!empty($data['image']) && !filter_var($data['image'], FILTER_VALIDATE_URL)) {
                return response()->json(['success' => false, 'message' => 'The image must be a valid URL or image file.'], 422);
            }

            $location = Location::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Location created successfully',
                'location' => $location->load('region')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // UPDATE LOCATION
    // ============================================================
    public function update(Request $request, $id)
    {
        $auth = $this->checkTouristModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $location = Location::find($id);
            if (!$location) {
                return response()->json(['message' => 'Location not found'], 404);
            }

            $data = $request->validate([
                'region_id' => 'sometimes|required|exists:regions,region_id',
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'image' => 'sometimes|nullable',
                'image_url' => 'sometimes|nullable|url|max:2048',
                'url' => 'sometimes|nullable|url|max:2048'
            ]);

            if (!$request->hasFile('image') && empty($data['image'])) {
                $data['image'] = $data['image_url'] ?? $data['url'] ?? null;
            }
            unset($data['image_url']);
            unset($data['url']);

            if ($request->hasFile('image')) {
                $request->validate(['image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120']);
                $data['image'] = Cloudinary::uploadApi()->upload(
                    $request->file('image')->getRealPath()
                )['secure_url'];
            } elseif (array_key_exists('image', $data) && !empty($data['image']) && !filter_var($data['image'], FILTER_VALIDATE_URL)) {
                return response()->json(['success' => false, 'message' => 'The image must be a valid URL or image file.'], 422);
            }

            $location->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Location updated successfully',
                'location' => $location->fresh()->load('region')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // DELETE LOCATION
    // ============================================================
    public function destroy($id)
    {
        $auth = $this->checkTouristModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $location = Location::find($id);
            if (!$location) {
                return response()->json(['message' => 'Location not found'], 404);
            }

            $location->delete();

            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // UPLOAD IMAGE
    // ============================================================
    public function uploadImage(Request $request)
    {
        $auth = $this->checkTouristModerator();
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
