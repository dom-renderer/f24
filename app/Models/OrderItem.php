<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
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
