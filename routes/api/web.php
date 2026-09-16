<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\WebProfileController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\TripController;
use App\Http\Controllers\Web\LocationController;
use App\Http\Controllers\Web\RegionController;
use App\Http\Controllers\Web\ProvinceController;
use App\Http\Controllers\Web\SuggestionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Web\ReportController;

// ============================================================
// Public Routes
// ============================================================
Route::post('/login', [WebAuthController::class, 'login']);
Route::post('/password/request', [PasswordResetController::class, 'requestReset']);
Route::post('/password/reset', [PasswordResetController::class, 'verifyAndReset']);

// ============================================================
// Protected Routes (Admin + Moderators)
// ============================================================
Route::middleware(['token.auth', 'role:admin|tourist-site-moderator|party-trip-moderator'])
    ->group(function () {

        Route::post('/logout', [WebAuthController::class, 'logout']);
        Route::get('/me', [WebProfileController::class, 'me']);
        Route::put('/me', [WebProfileController::class, 'update']);
        Route::delete('/me', [WebProfileController::class, 'deleteAccount']);
        Route::post('/me/change-password', [WebProfileController::class, 'changePassword']);

        Route::post('/me/upload-image', [WebProfileController::class, 'uploadImage']);

        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/overview', [DashboardController::class, 'overview']);

        Route::get('/locations', [LocationController::class, 'index']);
        Route::get('/locations/{id}', [LocationController::class, 'show']);

        Route::get('/regions', [RegionController::class, 'index']);
        Route::get('/regions/{id}', [RegionController::class, 'show']);

        Route::get('/provinces', [ProvinceController::class, 'index']);
        Route::get('/provinces/{id}', [ProvinceController::class, 'show']);
    });

// ============================================================
// tourist-site-moderator
// ============================================================
Route::middleware(['token.auth', 'role:tourist-site-moderator'])
    ->group(function () {
        Route::get('/suggestions', [SuggestionController::class, 'index']);
        Route::delete('/suggestions/{id}', [SuggestionController::class, 'destroy']);

        Route::post('/locations', [LocationController::class, 'store']);
        Route::put('/locations/{id}', [LocationController::class, 'update']);
        Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
        Route::post('/locations/upload-image', [LocationController::class, 'uploadImage']);

        Route::post('/regions', [RegionController::class, 'store']);
        Route::put('/regions/{id}', [RegionController::class, 'update']);
        Route::delete('/regions/{id}', [RegionController::class, 'destroy']);

        Route::post('/provinces', [ProvinceController::class, 'store']);
        Route::put('/provinces/{id}', [ProvinceController::class, 'update']);
        Route::delete('/provinces/{id}', [ProvinceController::class, 'destroy']);
    });

// ============================================================
// (Admin + Party-Trip-Moderator)
// ============================================================
Route::middleware(['token.auth', 'role:admin|party-trip-moderator'])
    ->group(function () {
        Route::get('/trips', [TripController::class, 'index']);
        Route::get('/trips/{id}', [TripController::class, 'show']);
    });

// ============================================================
// CRUD (Party-Trip-Moderator)
// ============================================================
Route::middleware(['token.auth', 'role:party-trip-moderator'])
    ->group(function () {
        Route::post('/trips', [TripController::class, 'store']);
        Route::put('/trips/{id}', [TripController::class, 'update']);
        Route::delete('/trips/{id}', [TripController::class, 'destroy']);

        Route::put('/trips/{id}/cancel', [TripController::class, 'cancel']);
        Route::put('/trips/{id}/confirm', [TripController::class, 'confirm']);

        Route::post('/trips/{tripId}/locations', [TripController::class, 'addLocation']);
        Route::delete('/trips/{tripId}/locations/{locationId}', [TripController::class, 'removeLocation']);

        Route::get('/trips/{tripId}/bookings', [TripController::class, 'bookings']);

        Route::post('/trips/upload-image', [TripController::class, 'uploadImage']);
    });

// ============================================================
// Admin
// ============================================================
Route::middleware(['token.auth', 'role:admin'])
    ->group(function () {

        Route::get('/reports/completed-trips', [ReportController::class, 'completedTrips']);
        Route::get('/reports/moderator/{id}', [ReportController::class, 'moderatorReport']);
        Route::get('/reports/moderators/all', [ReportController::class, 'allModeratorsReport']);

        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/{id}', [ReportController::class, 'show']);

        Route::get('/users', [AdminController::class, 'listUsers']);
        Route::get('/users/{id}', [AdminController::class, 'showUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        Route::get('/moderators', [AdminController::class, 'listModerators']);
        Route::post('/moderators', [AdminController::class, 'createModerator']);
        Route::put('/moderators/{id}', [AdminController::class, 'updateModerator']);
        Route::delete('/moderators/{id}', [AdminController::class, 'deleteModerator']);
    });
