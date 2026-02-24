<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class OrderPackagingMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function packagingMaterial()
    {
        return $this->belongsTo(PackagingMaterial::class, 'packaging_material_id');
    }
}
