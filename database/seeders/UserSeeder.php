<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UserSeeder extends Seeder
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
        // 1) Regular User (mobile)
        User::create([
            'name'              => 'Mobile User',
            'email'             => 'user@gmail.com',
            'password'          => Hash::make('password'),
            'phone'             => '+963999111222',
            'role'              => User::ROLE_USER,
            'email_verified_at' => now(),
            'profile_image'     => $this->uploadImage('profiles/default.jpg'),
        ]);

        // 2) Admin
        User::create([
            'name'              => 'Admin User',
            'email'             => 'admin@gmail.com',
            'password'          => Hash::make('password'),
            'phone'             => '+963999000000',
            'role'              => User::ROLE_ADMIN,
            'email_verified_at' => now(),
            'profile_image'     => $this->uploadImage('profiles/default.jpg'),
        ]);

        // 3) Party Trip Moderator
        User::create([
            'name'              => 'Party Moderator',
            'email'             => 'party@gmail.com',
            'password'          => Hash::make('password'),
            'phone'             => '+963999333444',
            'role'              => User::ROLE_PARTY_MOD,
            'email_verified_at' => now(),
            'profile_image'     => $this->uploadImage('profiles/default.jpg'),
        ]);

        // 4) Tourist Site Moderator
        User::create([
            'name'              => 'Tourist Moderator',
            'email'             => 'tourist@gmail.com',
            'password'          => Hash::make('password'),
            'phone'             => '+963999555666',
            'role'              => User::ROLE_TOURIST_MOD,
            'email_verified_at' => now(),
            'profile_image'     => $this->uploadImage('profiles/default.jpg'),
        ]);
    }
}
