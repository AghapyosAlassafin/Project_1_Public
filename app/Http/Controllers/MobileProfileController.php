<?php

namespace App\Http\Controllers;

use App\Models\EmailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class MobileProfileController extends Controller
{
  /**
   * Get authenticated mobile user's profile.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function me(Request $request)
  {
    return response()->json($request->user());
  }

  /**
   * Update mobile user's profile information.
   * Accepts a file for profile_image (via POST with _method=PUT) or a standard JSON PUT.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function update(Request $request)
  {
    $user = $request->user();

    if (!$user) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    $data = $request->validate([
      'name'          => 'sometimes|required|string|max:255',
      'phone'         => 'nullable|string|max:50',
      'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    if ($request->hasFile('profile_image')) {
      try {
        $data['profile_image'] = Cloudinary::uploadApi()->upload(
          $request->file('profile_image')->getRealPath()
        )['secure_url'];
      } catch (\Throwable $e) {
        return response()->json([
          'success' => false,
          'message' => 'Failed to upload profile image to Cloudinary: ' . $e->getMessage(),
        ], 500);
      }
    } else {
      unset($data['profile_image']);
    }

    $user->fill($data);
    $user->save();

    return response()->json($user);
  }

  /**
   * Change password for mobile user.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
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

    // Verify old password
    if (!Hash::check($data['old_password'], $user->password)) {
      return response()->json(['message' => 'Old password is incorrect'], 422);
    }

    // Prevent reusing old password
    if (Hash::check($data['password'], $user->password)) {
      return response()->json(['message' => 'New password cannot be the same as the old password'], 422);
    }

    // Update password
    $user->password = Hash::make($data['password']);
    $user->save();

    return response()->json(['message' => 'Password updated successfully']);
  }

  /**
   * Delete mobile user's account and all associated data.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function deleteAccount(Request $request)
  {
    $user = $request->user();

    // Delete all tokens
    $user->apiTokens()->delete();

    // Delete profile image if exists
    if ($user->profile_image) {
      Storage::disk('public')->delete($user->profile_image);
    }

    // Delete email verification records
    EmailVerification::where('user_id', $user->id)->delete();

    // Delete user
    $user->delete();

    return response()->json(['message' => 'Account deleted successfully']);
  }
}
