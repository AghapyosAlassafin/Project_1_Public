<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
  protected $primaryKey = 'suggestion_id';
  protected $fillable = ['user_id', 'description'];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
