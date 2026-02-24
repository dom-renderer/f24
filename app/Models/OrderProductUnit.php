<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProductUnit extends Model
{
    use HasFactory, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'order_product_id',
        'unit_id',
        'price',
        'status'
    ];

    public function product()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    public function unit()
    {
        return $this->belongsTo(OrderUnit::class, 'unit_id');
    }
}
