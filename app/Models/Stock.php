<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Stock::class);
    }
    public function location()
    {
        return $this->belongTo(Location::class);
    }

}
