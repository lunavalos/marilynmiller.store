<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'title_en',
        'title_es',
        'description_en',
        'description_es',
        'video_url',
        'thumbnail_url',
        'duration_minutes',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class);
    }
}
