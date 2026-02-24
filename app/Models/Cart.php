<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'receiver_store_id',
        'dealer_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function receiverStore()
    {
        return $this->belongsTo(Store::class, 'receiver_store_id');
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function otherItems()
    {
        return $this->hasMany(CartOtherItem::class);
    }
}
