<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Favorite extends Pivot
{
  protected $table = 'favorites';
  protected $primaryKey = 'favorit_id';
  public $incrementing = true;

  protected $fillable = ['user_id', 'location_id'];
}
