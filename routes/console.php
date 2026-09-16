<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;
use App\Models\EmailVerification;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule a background task to clean up unverified accounts every 15 minutes
Schedule::call(function () {

    // 1. Fetch unverified users who registered more than 15 minutes ago
    $expiredUsersQuery = User::whereNull('email_verified_at')
        ->where('created_at', '<', now()->subMinutes(15));

    $expiredUserIds = $expiredUsersQuery->pluck('id');

    if ($expiredUserIds->isNotEmpty()) {
        // 2. Delete related verification tokens first to prevent foreign key constraints
        EmailVerification::whereIn('user_id', $expiredUserIds)->delete();

        // 3. Permanently remove unverified users from the database
        $expiredUsersQuery->delete();
    }
})->everyFifteenMinutes();

Schedule::command('trips:update-statuses')->everyMinute();
