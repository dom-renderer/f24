<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Support\Facades\Auth;

class OrderObserver
{
    public function created(Order $order)
    {
        $this->log($order, 'created', 'Order Created', null, $order->toArray());
    }

    public function updated(Order $order)
    {
        $original = $order->getOriginal();
        $changes = $order->getChanges();
        
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $oldData = [];
        $newData = [];
        $description = [];

        foreach ($changes as $key => $value) {
            $oldData[$key] = $original[$key] ?? null;
            $newData[$key] = $value;

            $readableKey = ucwords(str_replace('_', ' ', $key));
            
            if ($key === 'status') {
                $statusLabels = Order::getStatuses();
                $oldStatus = $statusLabels[$original[$key]] ?? $original[$key];
                $newStatus = $statusLabels[$value] ?? $value;
                $description[] = "Status changed from '{$oldStatus}' to '{$newStatus}'";
            } else {
                $description[] = "{$readableKey} updated";
            }
        }

        $this->log($order, 'updated', implode(', ', $description), $oldData, $newData);
    }

    protected function log($order, $action, $description, $oldData, $newData)
    {
        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'old_data' => $oldData,
            'new_data' => $newData,
        ]);
    }
}
