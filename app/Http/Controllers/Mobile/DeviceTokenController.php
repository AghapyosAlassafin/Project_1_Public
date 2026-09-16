<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token'    => 'required|string',
            'platform' => 'nullable|string',
        ]);

        $user = $request->user();

        $user->deviceTokens()->updateOrCreate(
            ['token' => $data['token']],
            ['platform' => $data['platform']]
        );

        return response()->json(['message' => 'Token stored.']);
    }
}
