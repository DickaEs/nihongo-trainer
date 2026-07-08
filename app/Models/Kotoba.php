<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kotoba extends Model
{
    protected $fillable = [
        'chapter_id',
        'user_id',
        'romaji',
        'kana',
        'hiragana',
        'kanji',
        'meaning',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }
}
