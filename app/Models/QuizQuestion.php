<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
   protected $fillable = [
    'module_id', 'question_text', 'options', 'correct_option',
    'points', 'order', 'difficulty', 'domain', 'explanation'
];

    protected $casts = [
        'options' => 'array',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}