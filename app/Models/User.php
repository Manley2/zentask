<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use App\Models\FriendRequest;
use App\Models\Message;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'google_id',
        'google_avatar',
        'role',
        'subscription_plan',
        'plan_started_at',
        'is_subscribed',
        'subscribed_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan_started_at' => 'datetime',
            'subscribed_until' => 'datetime',
            'is_subscribed' => 'boolean',
        ];
    }

    /**
     * User has many tasks.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return Storage::url($this->avatar);
        }

        // Default avatar (initials)
        return '';
    }

    /**
     * Get subscription plan label
     */
    public function getPlanLabelAttribute(): string
    {
        return match ($this->subscription_plan) {
            'free' => 'Free Plan',
            'pro' => 'Pro Plan',
            'plus' => 'Plus Plan',
            default => 'Free Plan',
        };
    }

    /**
     * Get plan features
     */
    public function getPlanFeaturesAttribute(): array
    {
        return match ($this->subscription_plan) {
            'free' => [
                'voice_recorder' => false,
                'price' => 0,
            ],
            'pro' => [
                'voice_recorder' => true,
                'price' => 45000, // Rp 45.000
            ],
            'plus' => [
                'voice_recorder' => true,
                'price' => 65000, // Rp 65.000
            ],
            default => [
                'voice_recorder' => false,
                'price' => 0,
            ],
        };
    }

    /**
     * Check if user can access voice recorder
     */
    public function canUseVoiceRecorder(): bool
    {
        return $this->plan_features['voice_recorder'];
    }

    public function files()
    {
        return $this->hasMany(UserFile::class);
    }

    /**
     * Friend requests sent by this user.
     */
    public function friendRequestsSent()
    {
        return $this->hasMany(FriendRequest::class, 'requester_id');
    }

    /**
     * Friend requests received by this user.
     */
    public function friendRequestsReceived()
    {
        return $this->hasMany(FriendRequest::class, 'recipient_id');
    }

    /**
     * Accepted friends (mutual).
     */
    public function friends()
    {
        return $this->belongsToMany(
            self::class,
            'friend_requests',
            'requester_id',
            'recipient_id'
        )
        ->wherePivot('status', 'accepted')
        ->withTimestamps();
    }

    public function messagesSent()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messagesReceived()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Helper: check friendship (accepted either direction)
     */
    public function isFriendWith(User $other): bool
    {
        $friendRequest = FriendRequest::where(function ($q) use ($other) {
            $q->where('requester_id', $this->id)
              ->where('recipient_id', $other->id);
        })->orWhere(function ($q) use ($other) {
            $q->where('requester_id', $other->id)
              ->where('recipient_id', $this->id);
        })->where('status', 'accepted')->first();

        return (bool) $friendRequest;
    }

    public function isAdmin(): bool
    {
        return ($this->role ?? 'user') === 'admin';
    }
}
