<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotTrainingEntry extends Model
{
    use HasFactory;

    protected $table = 'chatbot_training_entries';

    protected $fillable = [
        'type',
        'trigger_text',
        'response_text',
        'metadata',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];
}
