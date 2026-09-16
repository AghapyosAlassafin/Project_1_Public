<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    const STATUS_DRAFT     = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ONGOING   = 'ongoing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $primaryKey = 'trip_id';

    protected $fillable = [
        'moderated_by',
        'name',
        'image',
        'start_date',
        'end_date',
        'capacity',
        'price',
        'discount',
        'travelers_number',
        'status',
        'description',
        'ratings_avg',
        'ratings_numbers'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'price'      => 'decimal:2',
        'discount'   => 'decimal:2',
    ];

    protected $appends = ['discounted_price', 'image_url'];

    public function getDiscountedPriceAttribute(): float
    {
        return round($this->price * (1 - ($this->discount / 100)), 2);
    }

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

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'trip_locations', 'trip_id', 'location_id')
            ->using(TripLocation::class)
            ->withPivot('sequence_order')
            ->orderBy('sequence_order')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_trips', 'trip_id', 'user_id')
            ->using(UserTrip::class)
            ->withPivot('payment_code', 'people_number', 'total_price', 'rate')
            ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(UserTrip::class, 'trip_id', 'trip_id');
    }
}
