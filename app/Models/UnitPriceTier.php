<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class UnitPriceTier extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = ['pricing_tier_id', 'product_id', 'product_unit_id', 'amount', 'status'];

    public function parent()
    {
        return $this->belongsTo(PricingTier::class, 'pricing_tier_id');
    }

    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'product_id');
    }

    public function unit()
    {
        return $this->belongsTo(OrderUnit::class, 'product_unit_id');
    }
}
