<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function thecity() {
        return $this->belongsTo(City::class, 'city', 'city_id');
    }

    public function dom() {
        return $this->belongsTo(User::class, 'dom_id');
    }

    public function storetype() {
        return $this->belongsTo(StoreType::class, 'store_type');
    }

    public function modeltype() {
        return $this->belongsTo(ModelType::class, 'model_type');
    }

    public function store() {
        return $this->belongsTo(Store::class, 'location');
    }

    public function subassets() {
        return $this->belongsToMany( Store::class, 'location_assets', 'asset_id', 'location_id' );
    }

    public function users() {
        return $this->belongsToMany(User::class, 'user_stores', 'store_id', 'user_id');
    }

    public function su() {
        return $this->hasMany(UserStore::class, 'store_id');
    }

    public function pricingTier() {
        return $this->belongsTo(PricingTier::class, 'pricing_tier_id');
    }

    public function ledgerTransactions()
    {
        return $this->hasMany(LedgerTransaction::class);
    }

    public function getBalanceAttribute()
    {
        // Total Debits - Total Credits
        // We use the service or direct query. Direct query is faster/easier for attribute.
        // We should cache this or be careful. For now, live calc.
        $debits = $this->ledgerTransactions()->where('type', 'debit')->where('status', 'active')->sum('amount');
        $credits = $this->ledgerTransactions()->where('type', 'credit')->where('status', 'active')->sum('amount');
        return $debits - $credits;
    }    
}