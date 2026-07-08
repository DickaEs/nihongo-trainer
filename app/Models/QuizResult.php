<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizResult extends Model
{
    protected $fillable = [
        'chapter_id',
        'user_id',
        'mode',
        'quiz_type',
        'total_questions',
        'correct_answers',
        'incorrect_answers',
        'score',
        'answers',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}
