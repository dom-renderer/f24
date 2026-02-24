<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PricingTier extends Model
{
    use HasFactory, softDeletes;

    protected $guarded = [];

    public function children()
    {
        return $this->hasMany(UnitPriceTier::class, 'pricing_tier_id');
    }

    public function store()
    {
        return $this->hasMany(Store::class, 'pricing_tier_id');
    }
}
