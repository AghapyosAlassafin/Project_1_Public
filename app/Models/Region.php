<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $primaryKey = 'region_id';
    protected $fillable = ['province_id', 'name'];

    /**
     * A region belongs to a province.
     */
    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id', 'province_id');
    }

    /**
     * A region has many locations.
     */
    public function locations()
    {
        return $this->hasMany(Location::class, 'region_id', 'region_id');
    }
}