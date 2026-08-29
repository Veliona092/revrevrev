<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    protected $fillable = [
        'lesson_id',
        'user_id',
        'progress',
        'scroll_position',
        'completed',
        'completed_at',
    ];

    protected $casts = [
        'progress' => 'decimal:2',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(SubpartLesson::class, 'lesson_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
