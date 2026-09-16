<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\MobileAuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\MobileProfileController;
use App\Http\Controllers\Mobile\TripController;
use App\Http\Controllers\Mobile\SuggestionController;
use App\Http\Controllers\Mobile\LocationController;
use App\Http\Controllers\Mobile\PublicDataController;
use App\Http\Controllers\Mobile\DeviceTokenController;
use App\Http\Controllers\Mobile\NotificationController;
/*
|--------------------------------------------------------------------------
| Public Mobile Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/verify', [VerificationController::class, 'verifyRegistration']);
Route::post('/login', [MobileAuthController::class, 'login']);
Route::post('/password/request', [PasswordResetController::class, 'requestReset']);
Route::post('/password/reset', [PasswordResetController::class, 'verifyAndReset']);
Route::post('/resend-code', [VerificationController::class, 'resendCode']);

/*
|--------------------------------------------------------------------------
| Protected Mobile Routes (Token Required + User Role Only)
|--------------------------------------------------------------------------
*/
Route::middleware(['token.auth', 'role:user'])->group(function () {
  // Authentication & Profile
  Route::post('/logout', [MobileAuthController::class, 'logout']);
  Route::get('/me', [MobileProfileController::class, 'me']);
  Route::put('/me', [MobileProfileController::class, 'update']);
  Route::delete('/me', [MobileProfileController::class, 'deleteAccount']);
  Route::post('/me/change-password', [MobileProfileController::class, 'changePassword']);

  // Trips
  Route::get('/trips', [TripController::class, 'index']);
  Route::get('/trips/my', [TripController::class, 'userTrips']);
  Route::get('/trips/history', [TripController::class, 'userPastTrips']);
  Route::get('/trips/{tripId}', [TripController::class, 'show']);
  Route::post('/trips/book', [TripController::class, 'book']);
  Route::post('/trips/cancel', [TripController::class, 'cancel']);
  Route::post('/trips/rate', [TripController::class, 'rate']);

  // Suggestions
  Route::post('/suggestions', [SuggestionController::class, 'store']);

  //weekly locations
  Route::get('/locations/weekly', [LocationController::class, 'weeklyLocation']);

  // Locations & Favorites
  Route::get('/locations/{locationId}', [LocationController::class, 'show']);
  Route::post('/favorites', [LocationController::class, 'addFavorite']);
  Route::get('/favorites', [LocationController::class, 'listFavorites']);
  Route::delete('/favorites', [LocationController::class, 'removeFavorite']);

  // Public data for filters (dropdowns)
  Route::get('/provinces', [PublicDataController::class, 'provinces']);
  Route::get('/regions', [PublicDataController::class, 'regions']);
  Route::get('/locations', [PublicDataController::class, 'locations']);

  Route::post('/device-token', [DeviceTokenController::class, 'store']);

  Route::get('/notifications', [NotificationController::class, 'index']);
  Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
  Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
