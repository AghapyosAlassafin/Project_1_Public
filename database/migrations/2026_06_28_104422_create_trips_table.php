<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id('trip_id');
            $table->foreignId('moderated_by')->nullable()->constrained('users', 'id')->nullOnDelete();
            $table->string('name');
            $table->string('image')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('capacity');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount', 5, 2)->default(0);              // ➕ خصم (%)
            $table->integer('travelers_number')->default(0);
            $table->string('status')->default('draft'); // draft, published, ongoing, cancelled, completed
            $table->text('description')->nullable();
            $table->float('ratings_avg')->default(0);
            $table->integer('ratings_numbers')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
