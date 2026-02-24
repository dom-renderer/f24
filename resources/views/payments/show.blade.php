@extends('layouts.app-master')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Payment Details</h1>
                    <div>
                        <a href="{{ route('payments.create') }}" class="btn btn-primary me-2">Create Another Payment</a>
                        <a href="{{ route('payments.index') }}" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Payment Info -->
            <div class="col-md-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Transaction Info</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th>Store:</th>
                                <td>{{ $payment->store->name }}</td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td>{{ $payment->payment_date->format('d-m-Y') }}</td>
                            </tr>
                            <tr>
                                <th>Amount:</th>
                                <td class="text-success fw-bold">₹{{ number_format($payment->amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Mode:</th>
                                <td>{{ $payment->payment_mode ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Reference:</th>
                                <td>{{ $payment->reference_no ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Remarks:</th>
                                <td>{{ $payment->remarks ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Created By:</th>
                                <td>{{ $payment->createdBy->name ?? '-' }}</td>
                            </tr>
                            @if($payment->attachment_path)
                                <tr>
                                    <th>Attachment:</th>
                                    <td>
                                        <div class="mt-2">
                                            @php
                                                $extension = pathinfo($payment->attachment_path, PATHINFO_EXTENSION);
                                                $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                                $url = Storage::url($payment->attachment_path);
                                            @endphp

                                            @if($isImage)
                                                <a href="{{ $url }}" target="_blank">
                                                    <img src="{{ $url }}" alt="Attachment Preview" class="img-thumbnail"
                                                        style="max-width: 200px;">
                                                </a>
                                                <div class="mt-1">
                                                    <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-external-link-alt"></i> View Full Image
                                                    </a>
                                                </div>
                                            @else
                                                <a href="{{ $url }}" target="_blank" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-paperclip"></i> View Attachment
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <!-- Allocation Waterfall -->
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Payment Allocation (Waterfall)</h6>
                        @if($payment->ledgerTransaction)
                            @php
                                $totalAllocated = $payment->ledgerTransaction->allocationsAsCredit->sum('allocated_amount');
                                $unallocated = $payment->amount - $totalAllocated;
                            @endphp
                            <span>
                                Allocated: <span class="text-success">₹{{ number_format($totalAllocated, 2) }}</span> |
                                Unallocated: <span class="text-danger">₹{{ number_format($unallocated, 2) }}</span>
                            </span>
                        @endif
                    </div>
                    <div class="card-body">
                        @if(!$payment->ledgerTransaction || $payment->ledgerTransaction->allocationsAsCredit->isEmpty())
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p>No allocations found for this payment.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Order Total</th>
                                            <th class="bg-success text-white">Paid by this Txn</th>
                                            <th>Current Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($payment->ledgerTransaction->allocationsAsCredit as $allocation)
                                            @php
                                                $debit = $allocation->debitTransaction;
                                                $order = $debit->order;
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if($order)
                                                        <a href="{{ route('orders.show', $order->id) }}" target="_blank">
                                                            {{ $order->order_number }} <i class="fas fa-external-link-alt small"></i>
                                                        </a>
                                                    @else
                                                        <span class="text-muted">N/A ({{ $debit->source_type }})</span>
                                                    @endif
                                                </td>
                                                <td>{{ $debit->txn_date->format('d-m-Y') }}</td>
                                                <td>₹{{ number_format($debit->amount, 2) }}</td>
                                                <td class="fw-bold text-success">
                                                    ₹{{ number_format($allocation->allocated_amount, 2) }}</td>
                                                <td>
                                                    @if($order)
                                                        {!! $order->payment_status_label !!}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection