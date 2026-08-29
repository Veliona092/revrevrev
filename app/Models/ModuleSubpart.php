<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleSubpart extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'description',
        'body',
        'file_path',
        'file_type',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(SubpartProgress::class, 'subpart_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(SubpartLesson::class, 'subpart_id')->orderBy('order');
    }

    /**
     * A sub-part is a "container" (shows a Lesson list to students) once it
     * has at least one Lesson; otherwise it's a "leaf" and shows its own
     * body/file_path directly, exactly as it does today. No extra flag
     * column needed — this is derived.
     */
    public function hasLessons(): bool
    {
        return $this->lessons()->exists();
    }
}
