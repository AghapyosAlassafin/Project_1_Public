<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Region;
use App\Models\Location;
use Illuminate\Http\Request;

class PublicDataController extends Controller
{
    /**
     * Get list of all provinces (for filter dropdowns).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function provinces()
    {
        $provinces = Province::select('province_id', 'name')->get();
        return response()->json($provinces);
    }

    /**
     * Get list of regions, optionally filtered by province.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function regions(Request $request)
    {
        $request->validate([
            'province_id' => 'nullable|integer|exists:provinces,province_id',
        ]);

        $query = Region::select('region_id', 'name', 'province_id');

        if ($provinceId = $request->input('province_id')) {
            $query->where('province_id', $provinceId);
        }

        return response()->json($query->get());
    }

    /**
     * Get list of locations, optionally filtered by region.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function locations(Request $request)
    {
        $request->validate([
            'region_id' => 'nullable|integer|exists:regions,region_id',
        ]);

        $query = Location::with('region.province'); // Load the province name.

        if ($regionId = $request->input('region_id')) {
            $query->where('region_id', $regionId);
        }

        $locations = $query->get()->map(function ($location) {
            return [
                'location_id'   => $location->location_id,
                'name'          => $location->name,
                'region_id'     => $location->region_id,
                'description'   => $location->description,
                'image'         => $location->image,
                'image_url'     => $location->image_url,
                'latitude'      => $location->latitude,
                'longitude'     => $location->longitude,
                'province_name' => $location->region->province->name ?? null,
            ];
        });

        return response()->json($locations);
    }
}
