<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $primaryKey = 'location_id';
    protected $fillable = [
        'region_id',
        'name',
        'description',
        'latitude',
        'longitude',
        'image'
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }

    /**
     * A location belongs to a region.
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'region_id');
    }

    /**
     * The trips that include this location (many-to-many via trip_locations).
     */
    public function trips()
    {
        return $this->belongsToMany(Trip::class, 'trip_locations', 'location_id', 'trip_id')
            ->using(TripLocation::class)
            ->withPivot('sequence_order')
            ->withTimestamps();
    }

    /**
     * Users who favorited this location.
     */
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites', 'location_id', 'user_id')
            ->using(Favorite::class)
            ->withTimestamps();
    }
}
