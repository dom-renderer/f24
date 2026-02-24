<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class OrderCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'category_id');
    }

    public function parent()
    {
        return $this->belongsTo(OrderCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OrderCategory::class, 'parent_id');
    }
}
