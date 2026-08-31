<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Lecture extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'lectures';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'teacher_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'mime_type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'uploaded_at' => 'datetime',
        'updated_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Relationship: belongs to a teacher (User)
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Accessor: full URL to download/view the file
     * (use this in Blade: {{ $lecture->file_url }})
     */
    public function getFileUrlAttribute()
    {
        // We'll create a protected route later: /teacher/lectures/{id}/file
        return route('teacher.lecture.file', $this->id);
    }

    /**
     * Accessor: human-readable file size (e.g. "2.4 MB")
     */
    public function getFileSizeHumanAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < 4; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 1).' '.$units[$i];
    }

    /**
     * Boot method: automatically set teacher_id on create
     */
    protected static function booted()
    {
        static::creating(function ($lecture) {
            if (auth()->check() && auth()->user()->role === 'teacher') {
                $lecture->teacher_id = auth()->id();
            }
        });

        // Optional: delete physical file when record is hard-deleted
        static::deleting(function ($lecture) {
            if (Storage::exists($lecture->file_path)) {
                Storage::delete($lecture->file_path);
            }
        });
    }
}
