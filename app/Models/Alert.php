<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $guarded = [];

    // The person who made the change
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}