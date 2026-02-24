<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartOtherItem extends Model
{
    protected $fillable = ['cart_id', 'other_item_id', 'quantity', 'price', 'price_includes_tax'];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function otherItem()
    {
        return $this->belongsTo(OtherItem::class, 'other_item_id');
    }
}
