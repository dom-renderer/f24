<?php

namespace App\Observers;

use App\Models\OrderProductPriceManagement;
use App\Models\PriceLog;
use Illuminate\Support\Facades\Auth;

class OrderProductPriceManagementObserver
{
    public function created(OrderProductPriceManagement $priceOverride)
    {
        $this->log($priceOverride, null, $priceOverride->price);
    }

    public function updated(OrderProductPriceManagement $priceOverride)
    {
        if ($priceOverride->isDirty('price')) {
            $this->log($priceOverride, $priceOverride->getOriginal('price'), $priceOverride->price);
        }
    }

    public function deleted(OrderProductPriceManagement $priceOverride)
    {
        $this->log($priceOverride, $priceOverride->price, null);
    }

    protected function log($priceOverride, $oldPrice, $newPrice)
    {
        // Don't log if prices are same (float comparison safe-ish for equality check here?)
        // if ($oldPrice === $newPrice) return; 

        PriceLog::create([
            'store_id' => $priceOverride->store_id,
            'user_id' => Auth::id(),
            'order_product_id' => $priceOverride->order_product_id,
            'unit_id' => $priceOverride->unit_id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
        ]);
    }
}
