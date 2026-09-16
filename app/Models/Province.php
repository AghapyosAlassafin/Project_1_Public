<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $primaryKey = 'province_id';
    protected $fillable = ['name'];

    /**
     * A province has many regions.
     */
    public function regions()
    {
        return $this->hasMany(Region::class, 'province_id', 'province_id');
    }
}