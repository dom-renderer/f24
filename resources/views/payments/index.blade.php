@extends('layouts.app-master')


@push('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>Manual Payments</h3>
                    <div>
                        <a href="{{ route('ledger.index') }}" class="btn btn-secondary me-2">Ledger Dashboard</a>
                        <a href="{{ route('payments.create') }}" class="btn btn-primary">Add New Payment</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <select id="filter_store_id" class="form-control">
                                <option value="">All Stores</option>
                                @foreach($stores as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="payments-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Store</th>
                                    <th>Amount (₹)</th>
                                    <th>Allocated</th>
                                    <th>Unallocated</th>
                                    <th>Mode</th>
                                    <th>Ref No</th>
                                    <th>Created By</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/other/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/js/other/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(function () {
            var table = $('#payments-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('payments.index') }}',
                    data: function (d) {
                        d.store_id = $('#filter_store_id').val();
                    }
                },
                columns: [
                    { data: 'payment_date', name: 'payment_date' },
                    { data: 'store.name', name: 'store.name' },
                    { data: 'amount', name: 'amount' },
                    { data: 'allocated', name: 'allocated', searchable: false },
                    { data: 'unallocated', name: 'unallocated', searchable: false },
                    { data: 'payment_mode', name: 'payment_mode' },
                    { data: 'reference_no', name: 'reference_no' },
                    { data: 'created_by.name', name: 'created_by.name', defaultContent: '-' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']]
            });

            $('#filter_store_id').change(function () {
                table.draw();
            });

            window.voidPayment = function (id) {
                if (confirm('Are you sure you want to VOID this payment? This will reverse the ledger entry.')) {
                    $.ajax({
                        url: '/payments/' + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function (res) {
                            if (res.status) {
                                alert(res.message);
                                table.draw();
                            } else {
                                alert(res.message);
                            }
                        },
                        error: function (err) {
                            alert('Error voiding payment.');
                        }
                    });
                }
            }
        });
    </script>
@endpush