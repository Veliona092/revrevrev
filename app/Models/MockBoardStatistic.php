<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MockBoardStatistic extends Model
{
    use HasFactory;

    protected $table = 'mock_board_statistics';

    protected $fillable = [
        'mock_board_id',
        'class_id',
        'pre_test_count',
        'pre_test_mean',
        'pre_test_std_dev',
        'pre_boards_count',
        'pre_boards_mean',
        'pre_boards_std_dev',
        'anova_f_statistic',
        'anova_p_value',
        'anova_significant',
        'improvement_percentage',
        'computed_at',
    ];

    protected $casts = [
        'pre_test_count' => 'integer',
        'pre_test_mean' => 'float',
        'pre_test_std_dev' => 'float',
        'pre_boards_count' => 'integer',
        'pre_boards_mean' => 'float',
        'pre_boards_std_dev' => 'float',
        'anova_f_statistic' => 'float',
        'anova_p_value' => 'float',
        'anova_significant' => 'boolean',
        'improvement_percentage' => 'float',
        'computed_at' => 'datetime',
    ];

    /**
     * The mock board these statistics belong to.
     */
    public function mockBoard(): BelongsTo
    {
        return $this->belongsTo(MockBoard::class);
    }

    /**
     * The class these statistics are for.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class);
    }

    /**
     * Get human-readable ANOVA interpretation.
     */
    public function getAnovaInterpretationAttribute(): string
    {
        if ($this->anova_significant === null) {
            return 'ANOVA not computed yet.';
        }

        if ($this->anova_significant) {
            $improvement = $this->improvement_percentage > 0 ? 'improved' : 'decreased';
            return "Significant difference found (p={$this->anova_p_value}). Scores {$improvement} by {$this->improvement_percentage}%.";
        }

        return "No significant difference found (p={$this->anova_p_value}).";
    }

    /**
     * Check if statistics are fresh (computed within last hour).
     */
    public function isFresh(): bool
    {
        if (!$this->computed_at) {
            return false;
        }

        return $this->computed_at->diffInHours(now()) < 1;
    }
}
