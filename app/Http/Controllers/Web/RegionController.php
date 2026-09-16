<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Region;

class RegionController extends Controller
{
    // ============================================================
    // Helpers
    // ============================================================

    /**
     * Only tourist-site-moderator can create/update/delete regions,
     * consistent with how Location CRUD is restricted.
     */
    private function checkTouristModerator()
    {
        $auth = Auth::user();
        if (!$auth || $auth->role !== 'tourist-site-moderator') {
            return null;
        }
        return $auth;
    }

    /**
     * Admin + both moderator roles can read regions.
     */
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
    // LIST REGIONS
    // ============================================================
    public function index()
    {
        $auth = $this->canRead();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $regions = Region::with('province')->latest()->get();

            return response()->json([
                'success' => true,
                'regions' => $regions,
                'count' => $regions->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // SHOW REGION
    // ============================================================
    public function show($id)
    {
        $auth = $this->canRead();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $region = Region::with('province', 'locations')->find($id);

            if (!$region) {
                return response()->json(['message' => 'Region not found'], 404);
            }

            return response()->json([
                'success' => true,
                'region' => $region
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // CREATE REGION
    // ============================================================
    public function store(Request $request)
    {
        $auth = $this->checkTouristModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $data = $request->validate([
                'province_id' => 'required|exists:provinces,province_id',
                'name' => 'required|string|max:255',
            ]);

            $region = Region::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Region created successfully',
                'region' => $region->load('province')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // UPDATE REGION
    // ============================================================
    public function update(Request $request, $id)
    {
        $auth = $this->checkTouristModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $region = Region::find($id);
            if (!$region) {
                return response()->json(['message' => 'Region not found'], 404);
            }

            $data = $request->validate([
                'province_id' => 'sometimes|required|exists:provinces,province_id',
                'name' => 'sometimes|required|string|max:255',
            ]);

            $region->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Region updated successfully',
                'region' => $region->fresh()->load('province')
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // DELETE REGION
    // ============================================================
    public function destroy($id)
    {
        $auth = $this->checkTouristModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $region = Region::find($id);
            if (!$region) {
                return response()->json(['message' => 'Region not found'], 404);
            }

            if ($region->locations()->exists()) {
                return response()->json([
                    'message' => 'Cannot delete region: it still has locations linked to it. Remove or reassign them first.'
                ], 422);
            }

            $region->delete();

            return response()->json([
                'success' => true,
                'message' => 'Region deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
