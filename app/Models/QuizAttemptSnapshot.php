<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizAttemptSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'quiz_stage',
        'mock_board_id',
        'phase_type',
        'attempt_number',
        'score',
        'total',
        'percentage',
        'passed',
        'started_at',
        'completed_at',
        'questions_snapshot',
    ];

    protected $casts = [
        'questions_snapshot' => 'array',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
