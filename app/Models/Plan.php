<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name_en', 'name_es', 'price', 'billing_cycle', 'description_en', 'description_es',
        'features', 'is_active', 'is_featured', 'sort_order', 'stripe_payment_link'
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function sessions()
    {
        return $this->belongsToMany(Session::class, 'plan_service_session');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class);
    }

    public function videos()
    {
        return $this->belongsToMany(Video::class);
    }

    public function liveEvents()
    {
        return $this->belongsToMany(LiveEvent::class);
    }
}
