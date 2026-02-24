<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtherItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'pricing_type',
        'price_per_piece',
        'price_includes_tax',
        'tax_slab_id',
        'status'
    ];

    public function taxSlab()
    {
        return $this->belongsTo(TaxSlab::class);
    }
}
