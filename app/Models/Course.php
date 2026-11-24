<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'title_en',
        'title_es',
        'description_en',
        'description_es',
        'thumbnail_url',
        'stripe_payment_link',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function modules()
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    public function plans()
    {
        return $this->belongsToMany(Plan::class);
    }
}
