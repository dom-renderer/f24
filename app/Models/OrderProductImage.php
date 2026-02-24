<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProductImage extends Model
{
    use HasFactory;

    protected $fillable = [ 'order_product_id', 'image_path' ];

    public function product() { return $this->belongsTo(OrderProduct::class, 'order_product_id'); }
}
