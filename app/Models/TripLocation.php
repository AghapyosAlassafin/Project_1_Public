<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TripLocation extends Pivot
{
  protected $table = 'trip_locations';
  protected $primaryKey = 'trip_location_id';
  public $incrementing = true;

  protected $fillable = ['trip_id', 'location_id', 'sequence_order'];

  /**
   * Get the trip that owns this pivot entry.
   */
  public function trip()
  {
    return $this->belongsTo(Trip::class, 'trip_id', 'trip_id');
  }

  /**
   * Get the location that owns this pivot entry.
   */
  public function location()
  {
    return $this->belongsTo(Location::class, 'location_id', 'location_id');
  }
}
