<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_trips', function (Blueprint $table) {
            $table->id('user_trip_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trip_id')->constrained('trips', 'trip_id')->cascadeOnDelete();
            $table->string('payment_code')->nullable();
            $table->integer('people_number')->default(1);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->tinyInteger('rate')->nullable()->unsigned()->comment('Rating 1-5');
            $table->timestamps();

            $table->unique(['user_id', 'trip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_trips');
    }
};
