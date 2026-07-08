<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $fillable = [
        'level',
        'name',
        'title',
        'slug',
        'position',
    ];

    public function kotobas()
    {
        return $this->hasMany(Kotoba::class);
    }
}
