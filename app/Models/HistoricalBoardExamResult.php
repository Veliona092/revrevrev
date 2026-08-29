<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HistoricalBoardExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'program',
        'exam_label',
        'exam_period_or_year',
        'total_examinees',
        'passed_count',
        'source_note',
        'entered_by',
    ];

    protected function casts(): array
    {
        return [
            'total_examinees' => 'integer',
            'passed_count' => 'integer',
        ];
    }

    /**
     * The admin/teacher who typed this record in.
     */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    /**
     * Mock boards linked to this historical record for comparison.
     */
    public function mockBoards(): HasMany
    {
        return $this->hasMany(MockBoard::class);
    }

    /**
     * The actual passing rate, computed from the raw counts rather than
     * stored directly, so the underlying numbers stay auditable.
     */
    public function getPassingRateAttribute(): float
    {
        if ($this->total_examinees <= 0) {
            return 0.0;
        }

        return round(($this->passed_count / $this->total_examinees) * 100, 2);
    }
}
