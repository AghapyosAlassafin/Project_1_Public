<?php

namespace App\Http\Controllers;

use App\Models\EmailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Return authenticated user's profile.
     */
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Update general profile information for the authenticated user.
     * Moderators are not allowed to edit their own accounts.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (in_array($user->role, ['tourist-site-moderator', 'party-trip-moderator'], true)) {
            return response()->json(['message' => 'Moderators cannot edit their accounts. Contact an admin.'], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'profile_image' => 'nullable|string',
        ]);

        $user->fill($data);
        $user->save();

        return response()->json($user);
    }

    /**
     * Change password using an last password.
     */

public function changePassword(Request $request)
{
    $data = $request->validate([
        'old_password' => 'required|string',
        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'regex:/[!@#$%^&*]/',
        ],
    ]);

    $user = $request->user();

    // Verify the old password.
    if (!Hash::check($data['old_password'], $user->password)) {
        return response()->json(['message' => 'Old password is incorrect'], 422);
    }

    // Prevent reusing the old password.
    if (Hash::check($data['password'], $user->password)) {
        return response()->json(['message' => 'New password cannot be the same as the old password'], 422);
    }

    // Update the password.
    $user->password = Hash::make($data['password']);
    $user->save();

    return response()->json(['message' => 'Password updated successfully']);
}
public function deleteAccount(Request $request)
{
    $user = $request->user();

    // Delete all tokens associated with the user.
    $user->apiTokens()->delete();

    // Delete the profile image if it exists.
    if ($user->profile_image) {
        Storage::disk('public')->delete($user->profile_image);
    }

    // Delete email verification records.
    EmailVerification::where('user_id', $user->id)->delete();

    // Delete the user.
    $user->delete();

    return response()->json(['message' => 'Account deleted successfully']);
}

}
