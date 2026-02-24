@extends('layouts.app-master')


@push('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}" />
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>Customer Ledger Dashboard</h3>
                    <a href="{{ route('payments.index') }}" class="btn btn-primary">View Payments</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="ledger-dashboard-table">
                            <thead>
                                <tr>
                                    <th>Store Name</th>
                                    <th>Code</th>
                                    <th>City</th>
                                    <th>Balance (₹)</th>
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
            $('#ledger-dashboard-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('ledger.index') }}',
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'code', name: 'code' },
                    { data: 'thecity.city_name', name: 'thecity.city_name', defaultContent: '-' },
                    {
                        data: 'balance',
                        name: 'balance',
                        render: function (data, type, row) {
                            var val = parseFloat(data.replace(/,/g, ''));
                            var color = val > 0 ? 'text-danger' : 'text-success';
                            return '<span class="' + color + ' font-weight-bold">' + data + '</span>';
                        }
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ]
            });
        });
    </script>
@endpush