<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserTrip extends Pivot
{
  protected $table = 'user_trips';
  protected $primaryKey = 'user_trip_id';
  public $incrementing = true;

  protected $fillable = [
    'user_id',
    'trip_id',
    'payment_code',
    'people_number',
    'total_price',
    'rate',
  ];

  /**
   * Boot events: auto-calculate total_price if not provided.
   */
  protected static function booted()
  {
    static::creating(function (UserTrip $userTrip) {
      if (empty($userTrip->total_price) && $userTrip->trip_id && $userTrip->people_number) {
        $trip = Trip::find($userTrip->trip_id);
        if ($trip) {
          $userTrip->total_price = $trip->discounted_price * $userTrip->people_number;
        }
      }
    });
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function trip()
  {
    return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
  }
}
