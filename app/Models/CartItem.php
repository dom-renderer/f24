<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'unit_id', 'quantity'];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(OrderProduct::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(OrderUnit::class, 'unit_id');
    }
}
