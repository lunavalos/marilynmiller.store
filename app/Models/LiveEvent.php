<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveEvent extends Model
{
    protected $fillable = [
        'title_en',
        'title_es',
        'description_en',
        'description_es',
        'meeting_url',
        'start_time',
        'end_time',
        'thumbnail_url',
        'is_published',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class);
    }
}
