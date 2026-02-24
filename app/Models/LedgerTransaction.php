<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $dates = ['txn_date', 'due_date', 'voided_at'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function allocationsAsCredit()
    {
        return $this->hasMany(LedgerAllocation::class, 'credit_txn_id');
    }

    public function allocationsAsDebit()
    {
        return $this->hasMany(LedgerAllocation::class, 'debit_txn_id');
    }

    // Explicit relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
