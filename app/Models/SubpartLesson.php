<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubpartLesson extends Model
{
    protected $fillable = [
        'subpart_id',
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

    public function subpart(): BelongsTo
    {
        return $this->belongsTo(ModuleSubpart::class, 'subpart_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'lesson_id');
    }
}
