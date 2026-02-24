<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Utencil extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function orderUtencils()
    {
        return $this->hasMany(OrderUtencil::class, 'utencil_id');
    }

    public function histories()
    {
        return $this->hasMany(OrderUtencilHistory::class, 'utencil_id');
    }
}

