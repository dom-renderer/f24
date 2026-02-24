<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerAllocation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function creditTransaction()
    {
        return $this->belongsTo(LedgerTransaction::class, 'credit_txn_id');
    }

    public function debitTransaction()
    {
        return $this->belongsTo(LedgerTransaction::class, 'debit_txn_id');
    }
}
