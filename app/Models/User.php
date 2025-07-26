<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscordPermissionEnum;
use App\Helpers\Discord\GetGuilds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $avatar
 * @property string|null $discord_id
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property \Illuminate\Support\Carbon|null $refresh_token_expires_at
 * @property \Illuminate\Support\Carbon|null $token_expires_at
 * @property bool $is_admin
 * @property bool $is_on_mailing_list
 * @property string|null $current_server_id
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property-read array<string, mixed> $guilds
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserIntegration> $integrations
 * @property-read int|null $integrations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\License> $licenses
 * @property-read int|null $licenses_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\UserSetting|null $settings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Cashier\Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User hasExpiredGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onGenericTrial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentServerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDiscordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsOnMailingList($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePmLastFour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePmType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRefreshTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTokenExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'access_token',
        'refresh_token',
        'refresh_token_expires_at',
        'token_expires_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be appended to the model.
     *
     * @var list<string>
     */
    protected $appends = ['guilds'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'refresh_token_expires_at' => 'datetime',
            'token_expires_at' => 'datetime',
            'is_admin' => 'boolean',
            'is_on_mailing_list' => 'boolean',
            'trial_ends_at' => 'datetime',
        ];
    }

    /**
     * Determine if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Get the guilds where the user has the given permission.
     *
     * @return array<string, mixed>
     */
    public function getGuildsAttribute(): array
    {
        $guilds = Cache::remember('user-guilds-' . $this->id, now()->addMinutes(1), function () {
            return (new GetGuilds($this))
                ->getGuildsWhereUserHasPermission(DiscordPermissionEnum::ADMINISTRATOR);
        });

        return $guilds;
    }

    public function integrations()
    {
        return $this->hasMany(UserIntegration::class);
    }

    /**
     * Get the licenses for the user.
     */
    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    /**
     * Get the active licenses for the user.
     */
    public function activeLicenses()
    {
        return $this->licenses()->active();
    }

    /**
     * Get the parked licenses for the user.
     */
    public function parkedLicenses()
    {
        return $this->licenses()->parked();
    }

    /**
     * Check if the user's Discord access token is expired.
     */
    public function hasExpiredDiscordToken(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    /**
     * Check if the user's Discord refresh token is expired.
     */
    public function hasExpiredDiscordRefreshToken(): bool
    {
        return $this->refresh_token_expires_at && $this->refresh_token_expires_at->isPast();
    }

    /**
     * Check if the user can refresh their Discord token.
     */
    public function canRefreshDiscordToken(): bool
    {
        return $this->refresh_token && ! $this->hasExpiredDiscordRefreshToken();
    }

    /**
     * Get the user's settings.
     */
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Get or create user settings.
     */
    public function getOrCreateSettings(): UserSetting
    {
        return $this->settings()->firstOrCreate([
            'user_id' => $this->id,
        ]);
    }
}
