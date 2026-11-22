<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSession extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'exercises_data' => 'array',
            'explainability_data' => 'array',
            'fatigue_reported' => 'boolean',
            'progressive_overload_applied' => 'boolean',
            'medical_disclaimer_displayed' => 'boolean',
            'completed_at' => 'datetime',
            'overload_percentage' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
