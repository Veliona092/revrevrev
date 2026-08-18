<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'user_id',
        'message',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function class()
    {
        return $this->belongsTo(ClassModel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() to match FDD relation naming.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }
}
