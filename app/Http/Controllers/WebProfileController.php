<?php

namespace App\Http\Controllers;

use App\Models\EmailVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class WebProfileController extends Controller
{
  private function checkWebAccess($user)
  {
    if (!$user) return false;

    return in_array($user->role, [
      'admin',
      'tourist-site-moderator',
      'party-trip-moderator'
    ]);
  }
  /**
   * Get authenticated web user's profile.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function me(Request $request)
  {
    return response()->json($request->user());
  }

  /**
   * Update web user's profile information.
   * Moderators cannot edit their own accounts.
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

    // Moderators cannot edit their accounts
    if ($user->isModerator()) {
      return response()->json([
        'message' => 'Moderators cannot edit their accounts. Contact an admin.'
      ], 403);
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

  public function uploadImage(Request $request)
  {
    $user = $request->user();

    if (!$this->checkWebAccess($user)) {
      return response()->json(['message' => 'Forbidden'], 403);
    }

    if (in_array($user->role, ['tourist-site-moderator', 'party-trip-moderator'])) {
      return response()->json([
        'message' => 'Moderators cannot edit their accounts. Contact an admin.'
      ], 403);
    }

    try {
      $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
      ]);

      $imageUrl = Cloudinary::uploadApi()->upload(
        $request->file('image')->getRealPath()
      )['secure_url'];

      $user->profile_image = $imageUrl;
      $user->save();

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

  /**
   * Change password for web user.
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
   * Delete web user's account and all associated data.
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
