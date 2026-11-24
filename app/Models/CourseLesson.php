<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseLesson extends Model
{
    protected $fillable = [
        'course_module_id',
        'title_en',
        'title_es',
        'description_en',
        'description_es',
        'video_url',
        'duration_minutes',
        'sort_order',
    ];

    public function module()
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }
}
