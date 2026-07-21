<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Cache;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'avatar',
        'provider',
        'provider_id',
        'coins',
        'reward_coins',
        'is_admin',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'is_active' => 'boolean',
        'admin_locked_until' => 'datetime',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $roleKey): bool
    {
        return $this->role?->key === $roleKey;
    }

    public function hasPermission(string $permissionKey): bool
    {
        // Admin legacy flag: compat = accès total
        if ($this->is_admin) {
            return true;
        }

        if (!$this->role_id) {
            return false;
        }

        $cacheKey = 'talashow.perms.user.' . $this->id;
        $keys = Cache::remember($cacheKey, 60, function () {
            $role = $this->role()->with('permissions')->first();
            return $role ? $role->permissions->pluck('key')->all() : [];
        });

        return in_array($permissionKey, $keys, true);
    }

    public function canAccessAdminApp(): bool
    {
        return $this->hasPermission('adminapp.access');
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('is_active', true)
            ->where('ends_at', '>', now());
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function userEpisodes()
    {
        return $this->hasMany(UserEpisode::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function seriesLikes()
    {
        return $this->hasMany(SeriesLike::class);
    }

    public function watchHistory()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function getTotalCoinsAttribute(): int
    {
        return $this->coins + $this->reward_coins;
    }

    public function canUnlockEpisode(int $coinsRequired): bool
    {
        return $this->hasActiveSubscription() || $this->total_coins >= $coinsRequired;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification((string) $token));
    }
}
