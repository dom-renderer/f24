<?php

namespace App\Observers;

use App\Models\OrderCharge;
use App\Models\OrderLog;
use Illuminate\Support\Facades\Auth;

class OrderChargeObserver
{
    public function created(OrderCharge $charge)
    {
        $this->log($charge, 'charge_created', 'Additional charge added', null, $charge->toArray());
    }

    public function updated(OrderCharge $charge)
    {
        $original = $charge->getOriginal();
        $changes = $charge->getChanges();

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
            $description[] = "Charge {$readableKey} updated";
        }

        $this->log($charge, 'charge_updated', implode(', ', $description), $oldData, $newData);
    }

    public function deleted(OrderCharge $charge)
    {
        $this->log($charge, 'charge_deleted', 'Additional charge removed', $charge->getOriginal(), null);
    }

    protected function log(OrderCharge $charge, $action, $description, $oldData, $newData)
    {
        if (!$charge->order_id) {
            return;
        }

        OrderLog::create([
            'order_id' => $charge->order_id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'old_data' => $oldData,
            'new_data' => $newData,
        ]);
    }
}

