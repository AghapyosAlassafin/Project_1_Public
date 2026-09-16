<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Trip;
use App\Models\User;
use App\Models\Location;
use App\Models\UserTrip;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class TripSeeder extends Seeder
{
    private function uploadImage(string $path): string
    {
        $fullPath = storage_path('app/public/' . ltrim($path, '/'));
        if (file_exists($fullPath)) {
            try {
                return Cloudinary::uploadApi()->upload($fullPath, [
                    'folder' => dirname($path)
                ])['secure_url'];
            } catch (\Exception $e) {
                return 'https://via.placeholder.com/600x400?text=' . urlencode(basename($path));
            }
        }
        return 'https://via.placeholder.com/600x400?text=' . urlencode(basename($path));
    }

    public function run(): void
    {
        $moderator = User::firstOrCreate(
            ['email' => 'moderator@example.com'],
            [
                'name' => 'Trip Moderator',
                'password' => bcrypt('password'),
                'role' => User::ROLE_PARTY_MOD,
                'email_verified_at' => now(),
            ]
        );

        $locations = Location::all();
        if ($locations->count() < 3) {
            $this->command->error('Please run LocationSeeder first!');
            return;
        }

        $attachLocations = function (Trip $trip, array $locationNames) use ($locations) {
            $ids = $locations->whereIn('name', $locationNames)->pluck('location_id')->toArray();
            foreach ($ids as $index => $id) {
                $trip->locations()->attach($id, ['sequence_order' => $index + 1]);
            }
        };

        // ========================
        // 1) Draft (1)
        // ========================
        $draft = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Summer Adventure (Draft)',
            'image' => $this->uploadImage('trips/trip_draft.jpg'),
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
            'capacity' => 30,
            'price' => 120.00,
            'discount' => 0,
            'status' => Trip::STATUS_DRAFT,
            'description' => 'A draft trip for testing.',
        ]);
        $attachLocations($draft, ['Umayyad Mosque', 'Al-Hamidiyah Souq', 'Azem Palace']);

        // ========================
        // 2) Published (15 available)
        // ========================
        $pub1 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Historical Damascus Tour',
            'image' => $this->uploadImage('trips/damascus_tour.jpg'),
            'start_date' => now()->addDays(3),
            'end_date' => now()->addDays(5),
            'capacity' => 20,
            'price' => 75.50,
            'discount' => 10,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Explore the ancient city of Damascus.',
        ]);
        $attachLocations($pub1, ['Umayyad Mosque', 'Al-Hamidiyah Souq', 'Mount Qasioun']);

        $pub2 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Coastal Gems Tour',
            'image' => $this->uploadImage('trips/aleppo_ongoing.jpg'),
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(9),
            'capacity' => 15,
            'price' => 95.00,
            'discount' => 5,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Discover the beautiful Syrian coast.',
        ]);
        $attachLocations($pub2, ['Arwad Island', 'Ugarit', 'Salah al-Din al-Ayyubi Castle']);

        $pub3 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Desert and Oasis Experience',
            'image' => $this->uploadImage('trips/lebanon_completed.jpg'),
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(6),
            'capacity' => 10,
            'price' => 60.00,
            'discount' => 0,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'A quick trip to Palmyra and beyond.',
        ]);
        $attachLocations($pub3, ['Palmyra Ancient Ruins', 'Krak des Chevaliers', 'Norias of Hama']);

        $pub4 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Northern Syria Explorer',
            'image' => $this->uploadImage('trips/sunset_full.jpg'),
            'start_date' => now()->addDays(14),
            'end_date' => now()->addDays(17),
            'capacity' => 12,
            'price' => 130.00,
            'discount' => 8,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'An adventure through the northern regions.',
        ]);
        $attachLocations($pub4, ['Aleppo Citadel', 'Great Mosque of Aleppo', 'Serjilla']);

        $pub5 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Damascus Countryside Escape',
            'image' => $this->uploadImage('trips/trip_draft.jpg'),
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(4),
            'capacity' => 18,
            'price' => 55.00,
            'discount' => 0,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Relax in the outskirts of Damascus.',
        ]);
        $attachLocations($pub5, ['Mount Qasioun', 'Damascus Opera House', 'Al-Hamidiyah Souq']);

        $pub6 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Silk Road Remnants',
            'image' => $this->uploadImage('trips/damascus_tour.jpg'),
            'start_date' => now()->addDays(8),
            'end_date' => now()->addDays(10),
            'capacity' => 22,
            'price' => 110.00,
            'discount' => 12,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Explore ancient trade route cities.',
        ]);
        $attachLocations($pub6, ['Apamea', 'Krak des Chevaliers', 'Palmyra Ancient Ruins']);

        $pub7 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Islands & Fortresses',
            'image' => $this->uploadImage('trips/aleppo_ongoing.jpg'),
            'start_date' => now()->addDays(12),
            'end_date' => now()->addDays(14),
            'capacity' => 16,
            'price' => 140.00,
            'discount' => 0,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Visit the only inhabited Syrian island and Crusader castles.',
        ]);
        $attachLocations($pub7, ['Arwad Island', 'Tartus Cathedral', 'Salah al-Din al-Ayyubi Castle']);

        $pub8 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Hama Water Wheels Trail',
            'image' => $this->uploadImage('trips/lebanon_completed.jpg'),
            'start_date' => now()->addDays(6),
            'end_date' => now()->addDays(7),
            'capacity' => 14,
            'price' => 45.00,
            'discount' => 0,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Discover the famous Norias and Roman ruins.',
        ]);
        $attachLocations($pub8, ['Norias of Hama', 'Apamea', 'Krak des Chevaliers']);

        $pub9 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Aleppo Old City Revival',
            'image' => $this->uploadImage('trips/aleppo_ongoing.jpg'),
            'start_date' => now()->addDays(9),
            'end_date' => now()->addDays(11),
            'capacity' => 19,
            'price' => 85.00,
            'discount' => 5,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Experience the rich history of Aleppo.',
        ]);
        $attachLocations($pub9, ['Aleppo Citadel', 'Al-Madina Souq', 'Great Mosque of Aleppo']);

        $pub10 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Palmyra Sunrise Trek',
            'image' => $this->uploadImage('trips/sunset_full.jpg'),
            'start_date' => now()->addDays(4),
            'end_date' => now()->addDays(5),
            'capacity' => 11,
            'price' => 70.00,
            'discount' => 0,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Watch the sunrise over the ancient ruins.',
        ]);
        $attachLocations($pub10, ['Palmyra Ancient Ruins', 'Krak des Chevaliers', 'Serjilla']);

        $pub11 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Damascus Night Lights',
            'image' => $this->uploadImage('trips/damascus_tour.jpg'),
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(3),
            'capacity' => 25,
            'price' => 35.00,
            'discount' => 0,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Enjoy Damascus by night.',
        ]);
        $attachLocations($pub11, ['Damascus Opera House', 'Mount Qasioun', 'Al-Hamidiyah Souq']);

        $pub12 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Coastal Breeze Getaway',
            'image' => $this->uploadImage('trips/trip_draft.jpg'),
            'start_date' => now()->addDays(11),
            'end_date' => now()->addDays(13),
            'capacity' => 17,
            'price' => 100.00,
            'discount' => 10,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Relaxing coastal retreat.',
        ]);
        $attachLocations($pub12, ['Arwad Island', 'Ugarit', 'Tartus Cathedral']);

        $pub13 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Krak Explorer',
            'image' => $this->uploadImage('trips/lebanon_completed.jpg'),
            'start_date' => now()->addDays(16),
            'end_date' => now()->addDays(18),
            'capacity' => 13,
            'price' => 125.00,
            'discount' => 0,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Castle hopping in western Syria.',
        ]);
        $attachLocations($pub13, ['Krak des Chevaliers', 'Salah al-Din al-Ayyubi Castle', 'Arwad Island']);

        $pub14 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Apamea Column Walk',
            'image' => $this->uploadImage('trips/aleppo_ongoing.jpg'),
            'start_date' => now()->addDays(13),
            'end_date' => now()->addDays(14),
            'capacity' => 21,
            'price' => 65.00,
            'discount' => 5,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Walk through the grand colonnade of Apamea.',
        ]);
        $attachLocations($pub14, ['Apamea', 'Norias of Hama', 'Krak des Chevaliers']);

        $pub15 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Syrian Highlands Tour',
            'image' => $this->uploadImage('trips/sunset_full.jpg'),
            'start_date' => now()->addDays(19),
            'end_date' => now()->addDays(22),
            'capacity' => 15,
            'price' => 135.00,
            'discount' => 0,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'Discover the mountains and valleys.',
        ]);
        $attachLocations($pub15, ['Mount Qasioun', 'Serjilla', 'Aleppo Citadel']);

        // ========================
        // 3) Fully booked (1)
        // ========================
        $full = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Fully Booked Sunset Cruise',
            'image' => $this->uploadImage('trips/sunset_full.jpg'),
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(1),
            'capacity' => 2,
            'price' => 50.00,
            'discount' => 0,
            'status' => Trip::STATUS_PUBLISHED,
            'description' => 'A full trip for testing capacity.',
            'travelers_number' => 2,
        ]);
        $attachLocations($full, ['Norias of Hama', 'Apamea', 'Damascus Opera House']);

        // ========================
        // 4) Ongoing (2)
        // ========================
        $ong1 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Aleppo Heritage Tour',
            'image' => $this->uploadImage('trips/aleppo_ongoing.jpg'),
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(2),
            'capacity' => 15,
            'price' => 90.00,
            'discount' => 5,
            'status' => Trip::STATUS_ONGOING,
            'description' => 'Currently running tour of Aleppo.',
        ]);
        $attachLocations($ong1, ['Aleppo Citadel', 'Al-Madina Souq', 'Great Mosque of Aleppo']);

        $ong2 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Homs & Hama Discovery',
            'image' => $this->uploadImage('trips/damascus_tour.jpg'),
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(1),
            'capacity' => 12,
            'price' => 70.00,
            'discount' => 0,
            'status' => Trip::STATUS_ONGOING,
            'description' => 'Visiting the norias, citadels, and ancient cities.',
        ]);
        $attachLocations($ong2, ['Norias of Hama', 'Krak des Chevaliers', 'Apamea']);

        // ========================
        // 5) Completed (2)
        // ========================
        $comp1 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Coastal Syria Tour',
            'image' => $this->uploadImage('trips/lebanon_completed.jpg'),
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(8),
            'capacity' => 25,
            'price' => 150.00,
            'discount' => 0,
            'status' => Trip::STATUS_COMPLETED,
            'description' => 'A finished trip along the Syrian coast.',
        ]);
        $attachLocations($comp1, ['Salah al-Din al-Ayyubi Castle', 'Arwad Island', 'Ugarit']);

        $comp2 = Trip::create([
            'moderated_by' => $moderator->id,
            'name' => 'Damascus Cultural Weekend',
            'image' => $this->uploadImage('trips/trip_draft.jpg'),
            'start_date' => now()->subDays(20),
            'end_date' => now()->subDays(18),
            'capacity' => 20,
            'price' => 85.00,
            'discount' => 15,
            'status' => Trip::STATUS_COMPLETED,
            'description' => 'Finished tour of the capital\'s landmarks.',
        ]);
        $attachLocations($comp2, ['Umayyad Mosque', 'Azem Palace', 'Damascus Opera House']);

        // ========================
        // 6) Create sample bookings (UserTrips)
        // ========================
        $regularUser = User::where('email', 'user@example.com')->first();
        if (!$regularUser) {
            $regularUser = User::create([
                'name' => 'Mobile User',
                'email' => 'user@example.com',
                'password' => bcrypt('password'),
                'phone' => '+963999111222',
                'role' => User::ROLE_USER,
                'email_verified_at' => now(),
                'profile_image' => $this->uploadImage('profiles/default.jpg'),
            ]);
        }

        // Booking for a completed trip (already ended) - with rating
        if ($comp1) {
            UserTrip::create([
                'user_id'       => $regularUser->id,
                'trip_id'       => $comp1->trip_id,
                'payment_code'  => 'PAY-DUMMY-COMP1',
                'people_number' => 2,
                'total_price'   => $comp1->discounted_price * 2,
                'rate'          => 5,
            ]);
        }

        // Booking for another completed trip - with rating
        if ($comp2) {
            UserTrip::create([
                'user_id'       => $regularUser->id,
                'trip_id'       => $comp2->trip_id,
                'payment_code'  => 'PAY-DUMMY-COMP2',
                'people_number' => 1,
                'total_price'   => $comp2->discounted_price * 1,
                'rate'          => 4,
            ]);
        }

        // Booking for an ongoing trip (in progress)
        if ($ong1) {
            UserTrip::create([
                'user_id'       => $regularUser->id,
                'trip_id'       => $ong1->trip_id,
                'payment_code'  => 'PAY-DUMMY-ONG1',
                'people_number' => 1,
                'total_price'   => $ong1->discounted_price * 1,
                'rate'          => null,
            ]);
        }

        // Booking for a future published trip (upcoming)
        if ($pub1) {
            UserTrip::create([
                'user_id'       => $regularUser->id,
                'trip_id'       => $pub1->trip_id,
                'payment_code'  => 'PAY-DUMMY-FUT1',
                'people_number' => 2,
                'total_price'   => $pub1->discounted_price * 2,
                'rate'          => null,
            ]);
        }

        // Optional: increment travelers_number for trips with bookings to keep consistency
        Trip::whereIn('trip_id', [$comp1->trip_id, $comp2->trip_id, $ong1->trip_id, $pub1->trip_id])
            ->incrementEach([
                'travelers_number' => 1, // for each trip we added at least one person
            ]);
    }
}
