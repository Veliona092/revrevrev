<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockBoardPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'mock_board_id',
        'phase_type',
        'title',
        'module_id',
        'question_ids',
        'is_same_questions',
    ];

    protected $casts = [
        'question_ids' => 'array',
        'is_same_questions' => 'boolean',
    ];

    /**
     * The mock board this phase belongs to.
     */
    public function mockBoard(): BelongsTo
    {
        return $this->belongsTo(MockBoard::class);
    }

    /**
     * The module created for this phase.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get a human-readable label for this phase type.
     */
    public function getPhaseLabelAttribute(): string
    {
        return $this->phase_type === 'pre_test' ? 'Pre-Test' : 'Pre-Boards';
    }
}
