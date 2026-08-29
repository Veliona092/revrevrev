<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'quiz_stage',
        'mock_board_id',
        'score',
        'total',
        'percentage',
        'passed',
        'attempted_at',
        'attempt_count',
        'ai_strong',
        'ai_weak',
        'ai_recommendation',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'passed' => 'boolean',
        'attempted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    // Existing relationship
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }
}
