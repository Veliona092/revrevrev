<?php

namespace App\Models;

use Database\Factories\TestBankQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestBankQuestion extends Model
{
    /** @use HasFactory<TestBankQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'created_by',
        'program',
        'question_text',
        'options',
        'correct_option',
        'points',
        'difficulty',
        'status',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_archived' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assessmentQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }
}
