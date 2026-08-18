<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockBoardAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mock_board_id',
        'phase_type',
        'quiz_attempt_id',
        'score',
        'total',
        'percentage',
        'passed',
        'attempt_count',
        'ai_strong',
        'ai_weak',
        'ai_recommendation',
    ];

    protected $casts = [
        'score' => 'integer',
        'total' => 'integer',
        'percentage' => 'integer',
        'passed' => 'boolean',
        'attempt_count' => 'integer',
    ];

    /**
     * The student who made this attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The mock board this attempt belongs to.
     */
    public function mockBoard(): BelongsTo
    {
        return $this->belongsTo(MockBoard::class);
    }

    /**
     * The underlying quiz attempt.
     */
    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    /**
     * Get phase label.
     */
    public function getPhaseLabelAttribute(): string
    {
        return $this->phase_type === 'pre_test' ? 'Pre-Test' : 'Pre-Boards';
    }

    /**
     * Scope: Filter by phase type.
     */
    public function scopeByPhase($query, string $phase)
    {
        return $query->where('phase_type', $phase);
    }

    /**
     * Scope: Get passed attempts only.
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }
}
