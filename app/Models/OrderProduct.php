<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'sku', 'hsn_code', 'category_id', 'status', 'description', 'tax_slab_id'
    ];

    public function category() { return $this->belongsTo(OrderCategory::class, 'category_id'); }
    public function units() { return $this->hasMany(OrderProductUnit::class, 'order_product_id'); }
    public function images() { return $this->hasMany(OrderProductImage::class, 'order_product_id'); }
    public function unitPriceTiers() { return $this->hasMany(UnitPriceTier::class, 'product_id'); }
    public function taxSlab(){  return $this->belongsTo(TaxSlab::class); }

}
