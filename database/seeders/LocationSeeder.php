<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\Region;
use App\Models\Location;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class LocationSeeder extends Seeder
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
        // ========================

        $damascusProvince = Province::create(['name' => 'Damascus']);

        $oldDamascus = Region::create(['name' => 'Old Damascus', 'province_id' => $damascusProvince->province_id]);
        Location::create(['region_id' => $oldDamascus->region_id, 'name' => 'Umayyad Mosque', 'description' => 'The Great Mosque of Damascus, an architectural masterpiece.', 'latitude' => 33.5119, 'longitude' => 36.3067, 'image' => $this->uploadImage('locations/umayyad.jpg')]);
        Location::create(['region_id' => $oldDamascus->region_id, 'name' => 'Al-Hamidiyah Souq', 'description' => 'A historic and vibrant market in the heart of old Damascus.', 'latitude' => 33.5106, 'longitude' => 36.3019, 'image' => $this->uploadImage('locations/hamidiyah.jpg')]);
        Location::create(['region_id' => $oldDamascus->region_id, 'name' => 'Azem Palace', 'description' => 'A stunning example of Damascene architecture.', 'latitude' => 33.5097, 'longitude' => 36.3056, 'image' => $this->uploadImage('locations/azem.jpg')]);

        $modernDamascus = Region::create(['name' => 'Modern Damascus', 'province_id' => $damascusProvince->province_id]);
        Location::create(['region_id' => $modernDamascus->region_id, 'name' => 'Damascus Opera House', 'description' => 'The main venue for performing arts in Damascus.', 'latitude' => 33.5140, 'longitude' => 36.2790, 'image' => $this->uploadImage('locations/opera.jpg')]);
        Location::create(['region_id' => $modernDamascus->region_id, 'name' => 'Mount Qasioun', 'description' => 'A mountain offering panoramic views of the city.', 'latitude' => 33.5450, 'longitude' => 36.2720, 'image' => $this->uploadImage('locations/qasioun.jpg')]);

        // ========================

        $aleppoProvince = Province::create(['name' => 'Aleppo']);

        $ancientAleppo = Region::create(['name' => 'Ancient Aleppo', 'province_id' => $aleppoProvince->province_id]);
        Location::create(['region_id' => $ancientAleppo->region_id, 'name' => 'Aleppo Citadel', 'description' => 'A large medieval fortified palace in the centre of the old city.', 'latitude' => 36.1997, 'longitude' => 37.1631, 'image' => $this->uploadImage('locations/citadel.jpg')]);
        Location::create(['region_id' => $ancientAleppo->region_id, 'name' => 'Al-Madina Souq', 'description' => 'The largest covered historic market in the world.', 'latitude' => 36.2028, 'longitude' => 37.1567, 'image' => $this->uploadImage('locations/madina_souq.jpg')]);
        Location::create(['region_id' => $ancientAleppo->region_id, 'name' => 'Great Mosque of Aleppo', 'description' => 'The largest and oldest mosque in the city of Aleppo.', 'latitude' => 36.1994, 'longitude' => 37.1569, 'image' => $this->uploadImage('locations/great_mosque_aleppo.jpg')]);

        $deadCities = Region::create(['name' => 'Dead Cities', 'province_id' => $aleppoProvince->province_id]);
        Location::create(['region_id' => $deadCities->region_id, 'name' => 'Serjilla', 'description' => 'Well-preserved ruins of a Byzantine settlement.', 'latitude' => 35.9334, 'longitude' => 36.6334, 'image' => $this->uploadImage('locations/serjilla.jpg')]);

        // ========================

        $homsProvince = Province::create(['name' => 'Homs']);

        $homsRegion = Region::create(['name' => 'Homs City', 'province_id' => $homsProvince->province_id]);
        Location::create(['region_id' => $homsRegion->region_id, 'name' => 'Krak des Chevaliers', 'description' => 'One of the most important preserved medieval castles in the world.', 'latitude' => 34.7566, 'longitude' => 36.2958, 'image' => $this->uploadImage('locations/krak.jpg')]);
        Location::create(['region_id' => $homsRegion->region_id, 'name' => 'Khalid ibn al-Walid Mosque', 'description' => 'A famous mosque and shrine in the city center.', 'latitude' => 34.7355, 'longitude' => 36.7157, 'image' => $this->uploadImage('locations/khalid_mosque.jpg')]);

        $palmyraRegion = Region::create(['name' => 'Palmyra', 'province_id' => $homsProvince->province_id]);
        Location::create(['region_id' => $palmyraRegion->region_id, 'name' => 'Palmyra Ancient Ruins', 'description' => 'The monumental ruins of a great city that was one of the most important cultural centres of the ancient world.', 'latitude' => 34.5624, 'longitude' => 38.2672, 'image' => $this->uploadImage('locations/palmyra.jpg')]);

        // ========================

        $latakiaProvince = Province::create(['name' => 'Latakia']);

        $latakiaCoast = Region::create(['name' => 'Coastal Latakia', 'province_id' => $latakiaProvince->province_id]);
        Location::create(['region_id' => $latakiaCoast->region_id, 'name' => 'Ugarit', 'description' => 'The ancient port city where the first alphabet was developed.', 'latitude' => 35.5872, 'longitude' => 35.7372, 'image' => $this->uploadImage('locations/ugarit.jpg')]);
        Location::create(['region_id' => $latakiaCoast->region_id, 'name' => 'Salah al-Din al-Ayyubi Castle', 'description' => 'A magnificent medieval fortress surrounded by pine forests.', 'latitude' => 35.5789, 'longitude' => 36.0489, 'image' => $this->uploadImage('locations/salahdin_castle.jpg')]);

        // ========================

        $tartusProvince = Province::create(['name' => 'Tartus']);

        $tartusRegion = Region::create(['name' => 'Tartus City', 'province_id' => $tartusProvince->province_id]);
        Location::create(['region_id' => $tartusRegion->region_id, 'name' => 'Arwad Island', 'description' => 'The only inhabited island in Syria, full of history and charm.', 'latitude' => 34.8562, 'longitude' => 35.8605, 'image' => $this->uploadImage('locations/arwad.jpg')]);
        Location::create(['region_id' => $tartusRegion->region_id, 'name' => 'Tartus Cathedral', 'description' => 'An impressive former Crusader cathedral, now a museum.', 'latitude' => 34.8900, 'longitude' => 35.8830, 'image' => $this->uploadImage('locations/tartus_cathedral.jpg')]);

        //=================

        $hamaProvince = Province::create(['name' => 'Hama']);

        $hamaRegion = Region::create(['name' => 'Hama City', 'province_id' => $hamaProvince->province_id]);
        Location::create(['region_id' => $hamaRegion->region_id, 'name' => 'Norias of Hama', 'description' => 'The famous ancient water wheels along the Orontes River.', 'latitude' => 35.1350, 'longitude' => 36.7550, 'image' => $this->uploadImage('locations/norias.jpg')]);
        Location::create(['region_id' => $hamaRegion->region_id, 'name' => 'Apamea', 'description' => 'An ancient Greek and Roman city with a spectacular colonnade.', 'latitude' => 35.4247, 'longitude' => 36.3986, 'image' => $this->uploadImage('locations/apamea.jpg')]);
    }
}
