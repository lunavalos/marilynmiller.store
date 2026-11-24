<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteContent extends Model
{
    protected $fillable = [
        'gallery',
        'testimonials',
        'faqs',
        'stats',
        'hero',
    ];

    protected $casts = [
        'gallery' => 'array',
        'testimonials' => 'array',
        'faqs' => 'array',
        'stats' => 'array',
        'hero' => 'array',
    ];
}
