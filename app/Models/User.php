<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Available roles
    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_TOURIST_MOD = 'tourist-site-moderator';
    public const ROLE_PARTY_MOD = 'party-trip-moderator';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'profile_image',
        'role',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['profile_image_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Boot callbacks to populate derived columns.
     */
    protected static function booted()
    {
        static::created(function (User $user) {
            // Ensure `user_id` duplicates the primary id for external reference if not set
            if (empty($user->user_id)) {
                $user->user_id = $user->id;
                $user->saveQuietly();
            }
        });
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        if (empty($this->profile_image)) {
            return null;
        }

        if (str_starts_with($this->profile_image, 'http://') || str_starts_with($this->profile_image, 'https://')) {
            return $this->profile_image;
        }

        return asset('storage/' . $this->profile_image);
    }

    /**
     * User has many API tokens (custom implementation).
     */
    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    /**
     * Email verifications associated with this email/user.
     */
    public function emailVerifications()
    {
        return $this->hasMany(EmailVerification::class);
    }

    /**
     * Create a new API token for the user and return the plain token string.
     */
    public function createApiToken(string $name = null, array $abilities = null): string
    {
        $plain = Str::random(60);
        $hash = Hash::make($plain);

        $token = $this->apiTokens()->create([
            'token_hash' => $hash,
            'name' => $name,
            'abilities' => $abilities ? json_encode($abilities) : null,
        ]);

        // Return a token in the form {id}|{plain} so it can be efficiently verified
        return $token->id . '|' . $plain;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [self::ROLE_TOURIST_MOD, self::ROLE_PARTY_MOD], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships (new)
    |--------------------------------------------------------------------------
    */

    /**
     * Suggestions submitted by this user.
     */
    public function suggestions()
    {
        return $this->hasMany(Suggestion::class);
    }

    /**
     * Locations that the user has marked as favorites.
     */
    public function favoriteLocations()
    {
        return $this->belongsToMany(Location::class, 'favorites', 'user_id', 'location_id')
            ->withTimestamps();
    }

    /**
     * Trips that the user moderates (if role is party-trip-moderator or admin).
     */
    public function moderatedTrips()
    {
        return $this->hasMany(Trip::class, 'moderated_by');
    }

    /**
     * Trips the user has booked (joined).
     */
    public function trips()
    {
        return $this->belongsToMany(Trip::class, 'user_trips', 'user_id', 'trip_id')
            ->withPivot('payment_code', 'people_number', 'rate')
            ->withTimestamps();
    }
    public function deviceTokens()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }
}
