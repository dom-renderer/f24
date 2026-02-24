@extends('layouts.app-master')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Record New Payment</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Store *</label>
                            <select name="store_id" class="form-control select2" required>
                                <option value="">Select Store</option>
                                @foreach($stores as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Amount (₹) *</label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Payment Mode</label>
                            <select name="payment_mode" class="form-control">
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="UPI">UPI</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Adjustment">Adjustment</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference No</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Cheque No / TXN ID">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="attachment" class="form-label">Attachment <small class="text-muted">(jpg, jpeg, png,
                                    webp, gif - Max 10MB)</small></label>
                            <input type="file" class="form-control" id="attachment" name="attachment"
                                accept=".jpg,.jpeg,.png,.webp,.gif">
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('payments.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">Record Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function () {
            $('.select2').select2({
                theme: 'classic',
                width: '100%'
            });
        });
    </script>
@endpush