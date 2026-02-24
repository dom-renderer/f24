<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderUtencilHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    const TYPE_SENT = 0;
    const TYPE_RECEIVED = 1;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function utencil()
    {
        return $this->belongsTo(Utencil::class, 'utencil_id');
    }
}

