<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_locations', function (Blueprint $table) {
            $table->id('trip_location_id');
            $table->foreignId('trip_id')->constrained('trips', 'trip_id')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations', 'location_id')->cascadeOnDelete();
            $table->integer('sequence_order');
            $table->timestamps();

            $table->unique(['trip_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_locations');
    }
};
