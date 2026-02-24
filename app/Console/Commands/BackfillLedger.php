<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\LedgerTransaction;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;

class BackfillLedger extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ledger:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing ledger debit transactions for dispatched/delivered orders.';

    /**
     * Execute the console command.
     */
    public function handle(LedgerService $ledgerService)
    {
        $this->info('Starting Ledger Backfill...');

        // Find orders with status Dispatched (2) or Delivered (3) or Completed (5)
        // that DO NOT have a corresponding ledger debit transaction.

        $orders = Order::whereIn('status', [2, 3, 5])
            ->whereNotNull('receiver_store_id')
            ->chunk(100, function ($orders) use ($ledgerService) {
                foreach ($orders as $order) {
                    $exists = LedgerTransaction::where('order_id', $order->id)
                        ->where('source_type', 'order')
                        ->where('type', 'debit')
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // Double check store exists
                    if (!$order->receiverStore) {
                        $this->warn("Skipping Order #{$order->order_number} (ID: {$order->id}): Receiver Store not found.");
                        continue;
                    }

                    $this->info("Processing Order #{$order->order_number} (ID: {$order->id})");

                    try {
                        // Use dispatched_at if available, else created_at date
                        $date = $order->dispatched_at
                            ? \Carbon\Carbon::parse($order->dispatched_at)->format('Y-m-d')
                            : $order->created_at->format('Y-m-d');

                        $ledgerService->createDebit(
                            $order->receiver_store_id,
                            $order->net_amount,
                            $date,
                            'order',
                            $order->id,
                            $order->id,
                            'Backfill: Order Dispatch #' . $order->order_number
                        );

                        $this->info(" -> Created Debit: ₹{$order->net_amount}");

                    } catch (\Exception $e) {
                        $this->error(" -> Failed: " . $e->getMessage());
                    }
                }
            });

        $this->info('Backfill Complete.');
    }
}
