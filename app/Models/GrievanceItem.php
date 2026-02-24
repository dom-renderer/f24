<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class GrievanceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function grievance()
    {
        return $this->belongsTo(Grievance::class, 'grievance_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
