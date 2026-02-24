<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitDiscountTier extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    const TYPE_PERCENTAGE = 0;
    const TYPE_FIXED = 1;

    public static function getTypes(): array
    {
        return [
            self::TYPE_PERCENTAGE => 'Percentage',
            self::TYPE_FIXED => 'Fixed Amount',
        ];
    }
    
    public function tp() {
        return $this->belongsTo(PricingTier::class, 'pricing_tier_id');
    }
}

