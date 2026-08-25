<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    protected $fillable = [
        'module_id', 'question_text', 'options', 'correct_option',
        'points', 'order', 'difficulty', 'domain', 'explanation', 'test_bank_question_id',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function testBankQuestion(): BelongsTo
    {
        return $this->belongsTo(TestBankQuestion::class);
    }
}
