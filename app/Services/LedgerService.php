<?php

namespace App\Services;

use App\Models\LedgerTransaction;
use App\Models\LedgerAllocation;
use App\Models\StoreLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Order;

class LedgerService
{
    /**
     * Create a Debit entry (e.g. Order Dispatch).
     */
    public function createDebit($storeId, $amount, $date, $sourceType, $sourceId, $orderId = null, $notes = null)
    {
        return $this->processTransaction(function () use ($storeId, $amount, $date, $sourceType, $sourceId, $orderId, $notes) {

            // 1. Create Debit Transaction
            $debit = LedgerTransaction::create([
                'store_id' => $storeId,
                'txn_date' => $date, // Usually dispatch date
                'type' => 'debit',
                'amount' => $amount,
                'due_date' => $date, // Due immediately upon dispatch
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'order_id' => $orderId,
                'notes' => $notes,
                'created_by' => auth()->id(),
                'created_ip' => request()->ip(),
            ]);

            // 2. Try to allocate any existing unallocated credits (FIFO)
            $allocations = $this->allocateCreditsToDebit($debit);

            return ['debit' => $debit, 'allocations' => $allocations];
        }, $storeId);
    }

    /**
     * Create a Credit entry (e.g. Payment, Return).
     */
    public function createCredit($storeId, $amount, $date, $sourceType, $sourceId, $paymentId = null, $notes = null, $refNo = null)
    {
        return $this->processTransaction(function () use ($storeId, $amount, $date, $sourceType, $sourceId, $paymentId, $notes, $refNo) {

            // 1. Create Credit Transaction
            $credit = LedgerTransaction::create([
                'store_id' => $storeId,
                'txn_date' => $date,
                'type' => 'credit',
                'amount' => $amount,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'payment_id' => $paymentId,
                'reference_no' => $refNo,
                'notes' => $notes,
                'created_by' => auth()->id(),
                'created_ip' => request()->ip(),
            ]);

            // 2. Allocate this credit to outstanding debits (FIFO)
            $allocations = $this->allocateCreditToOutstandingDebits($credit);

            return ['credit' => $credit, 'allocations' => $allocations];
        }, $storeId);
    }

    /**
     * Core FIFO Allocation: Allocate specific credit to outstanding debits
     */
    private function allocateCreditToOutstandingDebits(LedgerTransaction $credit)
    {
        $unallocatedAmount = $credit->amount - $credit->allocationsAsCredit()->sum('allocated_amount');

        if ($unallocatedAmount <= 0)
            return [];

        // Find outstanding debits for this store, ordered by date ASC, then ID ASC (FIFO)
        // We only fetch active debits
        $outstandingDebits = LedgerTransaction::where('store_id', $credit->store_id)
            ->where('type', 'debit')
            ->where('status', 'active')
            ->orderBy('txn_date', 'asc') // Strict FIFO by Date
            ->orderBy('id', 'asc')       // Then by ID
            ->get();

        $allocations = [];

        foreach ($outstandingDebits as $debit) {
            if ($unallocatedAmount <= 0.009)
                break; // Float epsilon safety

            $alreadyPaid = $debit->allocationsAsDebit()->whereNull('voided_at')->sum('allocated_amount');
            $due = $debit->amount - $alreadyPaid;

            if ($due <= 0.009)
                continue;

            $allocateNow = min($unallocatedAmount, $due);

            $allocation = LedgerAllocation::create([
                'store_id' => $credit->store_id,
                'credit_txn_id' => $credit->id,
                'debit_txn_id' => $debit->id,
                'allocated_amount' => $allocateNow,
                'allocated_at' => now(),
                'created_by' => auth()->id(),
                'created_ip' => request()->ip(),
            ]);

            $allocations[] = $allocation;

            $unallocatedAmount -= $allocateNow;

            // Update Order Payment Status
            $this->updateOrderPaymentStatus($debit);
        }

        return $allocations;
    }

    /**
     * Core FIFO Allocation: Allocate available credits to specific debit
     */
    private function allocateCreditsToDebit(LedgerTransaction $debit)
    {
        $alreadyPaid = $debit->allocationsAsDebit()->whereNull('voided_at')->sum('allocated_amount');
        $due = $debit->amount - $alreadyPaid;

        if ($due <= 0)
            return [];

        // Find unallocated credits
        $availableCredits = LedgerTransaction::where('store_id', $debit->store_id)
            ->where('type', 'credit')
            ->where('status', 'active')
            ->orderBy('txn_date', 'asc') // Strict FIFO
            ->orderBy('id', 'asc')
            ->get();

        $allocations = [];

        foreach ($availableCredits as $credit) {
            if ($due <= 0.009)
                break;

            $used = $credit->allocationsAsCredit()->whereNull('voided_at')->sum('allocated_amount');
            $available = $credit->amount - $used;

            if ($available <= 0.009)
                continue;

            $allocateNow = min($available, $due);

            $allocation = LedgerAllocation::create([
                'store_id' => $debit->store_id,
                'credit_txn_id' => $credit->id,
                'debit_txn_id' => $debit->id,
                'allocated_amount' => $allocateNow,
                'allocated_at' => now(),
                'created_by' => auth()->id(),
                'created_ip' => request()->ip(),
            ]);

            $allocations[] = $allocation;

            $due -= $allocateNow;
        }

        // Update Order Payment Status (outside loop for single debit)
        $this->updateOrderPaymentStatus($debit);

        return $allocations;
    }

    /**
     * Global Transaction Shell with Locking
     */
    private function processTransaction(callable $callback, $storeId)
    {
        $attempts = 0;
        $maxAttempts = 3;

        while ($attempts < $maxAttempts) {
            try {
                return DB::transaction(function () use ($callback, $storeId) {
                    // 1. Lock the store
                    // We use a dedicated table for locking to avoid locking the main stores table row which might be read elsewhere
                    $lock = StoreLock::firstOrCreate(['store_id' => $storeId]);

                    // Pessimistic Lock
                    StoreLock::where('store_id', $storeId)->lockForUpdate()->first();

                    // 2. Execute Logic
                    return $callback();
                });
            } catch (\Illuminate\Database\QueryException $e) {
                // MySQL Deadlock error code: 1213
                if ($e->errorInfo[1] == 1213) {
                    $attempts++;
                    usleep(100000); // 100ms wait
                    continue;
                }
                throw $e;
            }
        }

        throw new \Exception("Ledger Transaction Failed after {$maxAttempts} deadlock retries.");
    }

    public function getBalance($storeId)
    {
        // Total Debits - Total Credits
        $debits = LedgerTransaction::where('store_id', $storeId)
            ->where('type', 'debit')
            ->where('status', 'active')
            ->sum('amount');

        $credits = LedgerTransaction::where('store_id', $storeId)
            ->where('type', 'credit')
            ->where('status', 'active')
            ->sum('amount');

        return $debits - $credits;
    }

    /**
     * Update Order Payment Status based on allocation
     */
    private function updateOrderPaymentStatus(LedgerTransaction $debit)
    {
        if ($debit->source_type !== 'order' || !$debit->order_id) {
            return;
        }

        $order = Order::find($debit->order_id);
        if (!$order)
            return;

        // Calculate total allocated against this debit
        $totalPaid = $debit->allocationsAsDebit()->whereNull('voided_at')->sum('allocated_amount');

        // Use Debit amount as the "payable" amount for consistency with ledger.
        // Assuming Order Net Amount matches Debit Amount.
        $payableAmount = (float) $debit->amount;

        $newStatus = Order::PAYMENT_STATUS_UNPAID;

        if ($totalPaid >= ($payableAmount - 0.01)) {
            $newStatus = Order::PAYMENT_STATUS_PAID;
        } elseif ($totalPaid > 0.01) {
            $newStatus = Order::PAYMENT_STATUS_PARTIAL;
        }

        if ($order->payment_status !== $newStatus) {
            $order->update(['payment_status' => $newStatus]);
        }
    }
}
