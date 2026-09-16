<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('email_verifications', function (Blueprint $table) {
      $table->id();
      $table->string('email')->index();
      $table->unsignedBigInteger('user_id')->nullable()->index();
      $table->string('code', 6);
      $table->enum('type', ['registration', 'password_reset', 'password_change'])->default('registration');
      $table->timestamp('expires_at')->nullable();
      $table->boolean('used')->default(false);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('email_verifications');
  }
};
