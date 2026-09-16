<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

/**
 * Main API Routes
 * 
 * This file includes both mobile and web API routes with appropriate prefixes.
 */

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API Routes
| Prefix: /api/mobile
| For: Regular users (ROLE_USER) from mobile application
|--------------------------------------------------------------------------
*/
Route::prefix('mobile')->group(base_path('routes/api/mobile.php'));

/*
|--------------------------------------------------------------------------
| Web API Routes
| Prefix: /api/web
| For: Admin and moderators from React web application
|--------------------------------------------------------------------------
*/
Route::prefix('web')->group(base_path('routes/api/web.php'));


Route::get('/run-scheduler', function (Request $request) {
    // حماية بسيطة باستخدام توكن سري
    if ($request->query('token') !== '5f4dcc3b5aa765d61d8327deb882cf99') {
        abort(403, 'Unauthorized');
    }

    Artisan::call('schedule:run');

    return 'Scheduler executed';
});