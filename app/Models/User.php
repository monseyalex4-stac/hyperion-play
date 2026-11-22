<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'interview_completed' => 'boolean',
            'weight_kg' => 'float',
            'height_cm' => 'float',
            'body_fat_pct' => 'float',
            'available_time_minutes' => 'integer',
            'current_level' => 'integer',
            'current_xp' => 'integer',
            'gender' => 'string',
        ];
    }

    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSession::class);
    }

    public function quests(): HasMany
    {
        return $this->hasMany(UserQuest::class);
    }
}
