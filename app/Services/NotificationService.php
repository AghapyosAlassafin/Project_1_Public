<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Trip;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationService
{
    /**
     * Send notification to a user (FCM + database).
     */
    public function sendToUser(User $user, string $title, string $body, string $type = 'system', array $data = [])
    {
        // 1. Save to database
        $notification = Notification::create([
            'user_id' => $user->id,
            'title'   => $title,
            'body'    => $body,
            'type'    => $type,
            'data'    => $data,
        ]);

        // 2. Send push via Firebase if user has device tokens
        $tokens = $user->deviceTokens()->pluck('token')->toArray();
        if (!empty($tokens)) {
            $this->sendPush($tokens, $title, $body, $data);
        }

        return $notification;
    }

    /**
     * Send push notification to multiple tokens.
     */
    private function sendPush(array $tokens, string $title, string $body, array $data = [])
    {
        $messaging = app('firebase.messaging');

        $message = CloudMessage::new()
            ->withNotification(FirebaseNotification::create($title, $body)) // Standard notification.
            ->withData($data)
            ->withHighestPossiblePriority();

        $messaging->sendMulticast($message, $tokens);
    }

    /**
     * Notify users who have favorited locations of a new trip.
     */
    public function notifyFavoriteLocationUsers(Trip $trip)
    {
        $locationIds = $trip->locations()->pluck('locations.location_id')->toArray();
        $users = User::whereHas('favoriteLocations', function ($q) use ($locationIds) {
            $q->whereIn('locations.location_id', $locationIds);
        })->get();

        foreach ($users as $user) {
            $this->sendToUser(
                $user,
                'New Trip Available',
                "A new trip '{$trip->name}' passes by your favorite locations!",
                'trip',
                ['trip_id' => $trip->trip_id]
            );
        }
    }

    /**
     * Notify all users who booked a trip that it has been updated.
     */
    public function notifyTripUpdated(Trip $trip)
    {
        $users = $trip->users;
        foreach ($users as $user) {
            $this->sendToUser(
                $user,
                'Trip Updated',
                "The trip '{$trip->name}' has been updated. Check the new details.",
                'trip',
                ['trip_id' => $trip->trip_id]
            );
        }
    }
}
