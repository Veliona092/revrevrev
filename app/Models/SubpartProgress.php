<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubpartProgress extends Model
{
    protected $fillable = [
        'subpart_id',
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

    public function subpart(): BelongsTo
    {
        return $this->belongsTo(ModuleSubpart::class, 'subpart_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
