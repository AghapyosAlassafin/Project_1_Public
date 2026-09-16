<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Province;

class ProvinceController extends Controller
{
    // ============================================================
    // Helpers - same pattern as RegionController.
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
    // LIST PROVINCES
    // ============================================================
    public function index()
    {
        $auth = $this->canRead();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $provinces = Province::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'provinces' => $provinces
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // SHOW PROVINCE
    // ============================================================
    public function show($id)
    {
        $auth = $this->canRead();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $province = Province::with('regions')->find($id);
        if (!$province) {
            return response()->json(['message' => 'Province not found'], 404);
        }

        return response()->json([
            'success' => true,
            'province' => $province
        ]);
    }

    // ============================================================
    // CREATE PROVINCE
    // ============================================================
    public function store(Request $request)
    {
        $auth = $this->checkTouristModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $data = $request->validate([
                'name' => 'required|string|max:255|unique:provinces,name',
            ]);

            $province = Province::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Province created successfully',
                'province' => $province
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // UPDATE PROVINCE
    // ============================================================
    public function update(Request $request, $id)
    {
        $auth = $this->checkTouristModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $province = Province::find($id);
            if (!$province) {
                return response()->json(['message' => 'Province not found'], 404);
            }

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:provinces,name,' . $id . ',province_id',
            ]);

            $province->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Province updated successfully',
                'province' => $province->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ============================================================
    // DELETE PROVINCE
    // ============================================================
    public function destroy($id)
    {
        $auth = $this->checkTouristModerator();
        if (!$auth) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $province = Province::find($id);
            if (!$province) {
                return response()->json(['message' => 'Province not found'], 404);
            }
            if ($province->regions()->exists()) {
                return response()->json([
                    'message' => 'Cannot delete province: it still has regions linked to it. Remove or reassign them first.'
                ], 422);
            }

            $province->delete();

            return response()->json([
                'success' => true,
                'message' => 'Province deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
