<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MockBoardPhase extends Model
{
    use HasFactory;

    protected $fillable = [
        'mock_board_id',
        'phase_type',
        'sequence_number',
        'label',
        'title',
        'module_id',
        'question_ids',
        'is_same_questions',
    ];

    protected $casts = [
        'question_ids' => 'array',
        'is_same_questions' => 'boolean',
        'sequence_number' => 'integer',
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
     * All cached student attempts for this specific phase.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(MockBoardAttempt::class);
    }

    /**
     * Get a human-readable label for this phase type.
     *
     * Falls back to a numbered "Post-Test N" label when a mock board has
     * more than one phase of the same phase_type and no custom label was
     * set, so multiple post-tests remain distinguishable in the UI.
     */
    public function getPhaseLabelAttribute(): string
    {
        if (! empty($this->label)) {
            return $this->label;
        }

        if ($this->phase_type === 'pre_test') {
            return $this->sequence_number > 1
                ? "Pre-Test {$this->sequence_number}"
                : 'Pre-Test';
        }

        return $this->sequence_number > 1
            ? "Post-Test {$this->sequence_number}"
            : 'Pre-Boards';
    }

    /**
     * Scope: Only post-test-type phases (currently stored as 'pre_boards').
     */
    public function scopePostTests($query)
    {
        return $query->where('phase_type', 'pre_boards');
    }
}
