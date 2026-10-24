<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const DEFAULT_PREFERENCES = [
        'renewal_reminders'    => true,
        'trial_reminders'      => true,
        'expired_alerts'       => true,
        'reminder_days'        => 3,
        'default_billing_cycle' => 'monthly',
        'default_currency'     => 'PHP',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'preferences',
        'last_login_at',
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
            'preferences' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * The subscriptions owned by this user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Stored preferences merged on top of the built-in defaults.
     *
     * @return array<string, mixed>
     */
    public function preferencesWithDefaults(): array
    {
        return array_replace(self::DEFAULT_PREFERENCES, $this->preferences ?? []);
    }

    /**
     * Record the current time as the user's last successful login.
     */
    public function markLastLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->save();
    }
}
