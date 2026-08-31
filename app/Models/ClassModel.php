<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'classes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'program',
        'code',
        'school_year',
        "year_level",
        'description',
        'created_by',
        'ai_summary',
        'assessment_ai_summary',
        'ai_settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'school_year' => 'integer',
        'ai_settings' => 'array',
    ];

    // ────────────────────────────────────────────────
    // Relationships
    // ────────────────────────────────────────────────

    /**
     * The user who created this class (teacher/admin)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * All users (students/teachers/etc.) enrolled in this class
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_user', 'class_id', 'user_id')
            ->withPivot('joined_at', 'assessment_ai_analysis')
            ->withTimestamps();
    }

    /**
     * Alias: treat as students (most common usage in your context)
     */
    public function students(): BelongsToMany
    {
        return $this->users()->where('users.role', 'student');
    }

    public function modules()
    {
        return $this->hasMany(Module::class, 'class_id');
    }

    /**
     * Announcements posted in this class
     */
    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'class_id');
    }
}
