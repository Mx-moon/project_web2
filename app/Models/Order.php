<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
protected $guarded = [];

protected $casts = [
'dispatched_at' => 'datetime',
'received_at' => 'datetime',
];

// Only the orders this person should see: Kasikas staff see orders for their
// showroom, FiNALi staff see orders from their warehouse, and the admin sees all.
public function scopeForUser($query, $user)
{
return $query
->when($user->role === 'kasikas', fn ($q) => $q->where('to_location_id', $user->location_id))
->when($user->role === 'finali', fn ($q) => $q->where('from_location_id', $user->location_id));
}

public function product()
{
return $this->belongsTo(Product::class);
}

public function fromLocation()
{
return $this->belongsTo(Location::class, 'from_location_id');
}

public function toLocation()
{
return $this->belongsTo(Location::class, 'to_location_id');
}

public function requester()
{
return $this->belongsTo(User::class, 'requested_by');
}
}