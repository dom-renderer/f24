<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Models\OrderLog;
use Illuminate\Support\Facades\Auth;

class OrderItemObserver
{
    public function created(OrderItem $item)
    {
        $productName = $item->product->name ?? 'Product';
        $quantity = $item->quantity;
        $unit = $item->unit->name ?? 'Unit';

        $this->log($item->order_id, 'item_added', "Added {$quantity} {$unit} of {$productName}", null, $item->toArray());
    }

    public function updated(OrderItem $item)
    {
        $original = $item->getOriginal();
        $changes = $item->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) return;

        $productName = $item->product->name ?? 'Product';
        $description = "Updated {$productName}: ";
        $details = [];

        foreach ($changes as $key => $value) {
            if ($key === 'quantity') {
                $details[] = "Quantity changed from {$original[$key]} to {$value}";
            } elseif ($key === 'unit_price') {
                $details[] = "Price changed from {$original[$key]} to {$value}";
            }
        }

        if (empty($details)) return; // Skip if only boring fields changed

        $this->log($item->order_id, 'item_updated', $description . implode(', ', $details), $original, $item->toArray());
    }

    public function deleted(OrderItem $item)
    {
        $productName = $item->product->name ?? 'Product';
        $quantity = $item->quantity;
        
        $this->log($item->order_id, 'item_removed', "Removed {$quantity} of {$productName}", $item->toArray(), null);
    }

    protected function log($orderId, $action, $description, $oldData, $newData)
    {
        OrderLog::create([
            'order_id' => $orderId,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'old_data' => $oldData,
            'new_data' => $newData,
        ]);
    }
}
