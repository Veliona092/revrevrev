<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id', 'title', 'description', 'file_path', 'file_type', 'order',
        'is_quiz', 'is_assignment', 'is_formal_assessment', 'time_limit', 'passing_grade', 'visibility',
        'created_by', 'is_mock_board', 'due_date', 'max_attempts',
    ];

    protected $casts = [
        'is_quiz' => 'boolean',
        'is_assignment' => 'boolean',
        'is_formal_assessment' => 'boolean',
        'passing_grade' => 'integer',
        'max_attempts' => 'integer',
        'due_date' => 'datetime',
    ];

    /**
     * True kung may due date na, at lagpas na ang oras ngayon.
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->due_date->isPast();
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ModuleProgress::class);
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function visibleTo(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'module_user_visibility', 'module_id', 'user_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function attemptGrants(): HasMany
    {
        return $this->hasMany(AssessmentAttemptGrant::class);
    }

    /**
     * Ang totoong bilang ng attempts na pinapayagan para sa estudyanteng ito:
     * base max_attempts ng module + anumang extra na ibinigay ng teacher.
     */
    public function allowedAttemptsFor(int $userId): int
    {
        $base = $this->max_attempts ?? 1;

        $grant = $this->relationLoaded('attemptGrants')
            ? $this->attemptGrants->firstWhere('user_id', $userId)
            : $this->attemptGrants()->where('user_id', $userId)->first();

        return $base + ($grant->extra_attempts ?? 0);
    }
}
