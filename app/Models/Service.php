<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'type', 'description', 'duration_minutes', 'stripe_payment_link'];

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }
}
