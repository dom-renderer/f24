@extends('layouts.app-master')


@push('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/daterangepicker.css') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}" />
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>Ledger Statement: {{ $store->name }} ({{ $store->code }})</h3>
                    <div class="text-end">
                        <div class="mb-2">
                            <div class="d-inline-flex align-items-center me-2 border rounded px-2 py-1 bg-white" style="cursor: pointer;" id="daterange-btn">
                                <i class="far fa-calendar me-2 text-muted"></i>
                                <span id="date_range_display">Select Date Range</span>
                                <input type="hidden" name="date_range" id="date_range" value="">
                            </div>
                            <a href="{{ route('ledger.export_pdf', $store->id) }}" class="btn btn-outline-danger btn-sm" id="btn-export-pdf"><i
                                    class="bi bi-file-pdf"></i> PDF</a>
                            <a href="{{ route('ledger.export_excel', $store->id) }}"
                                class="btn btn-outline-success btn-sm" id="btn-export-excel"><i class="bi bi-file-excel"></i> Excel</a>
                        </div>
                        <h4 class="mb-0">Current Balance: <span
                                class="@if($balance > 0) text-danger @else text-success @endif">₹
                                {{ number_format($balance, 2) }}</span></h4>
                        <small class="text-muted">Positive = Payable (Debit), Negative = Advance (Credit)</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="ledger-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Ref No</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Debit (₹)</th>
                                    <th>Credit (₹)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal (Simplified) -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1">
        <!-- ... form to post to payment.store ... -->
    </div>

@endsection

@push('js')
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/js/other/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/js/other/dataTables.bootstrap5.min.js') }}"></script>
    <script>
        $(function () {
            $('#ledger-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('ledger.show', $store->id) }}',
                    data: function(d) {
                        d.date_range = $('#date_range').val();
                    }
                },
                columns: [
                    { data: 'txn_date', name: 'txn_date' },
                    { data: 'reference_no', name: 'reference_no', defaultContent: '-' },
                    { data: 'type', name: 'type' },
                    { data: 'notes', name: 'notes' },
                    {
                        data: 'amount',
                        name: 'debit',
                        render: function (data, type, row) {
                            return row.type.toLowerCase() === 'debit' ? data : '-';
                        }
                    },
                    {
                        data: 'amount',
                        name: 'credit',
                        render: function (data, type, row) {
                            return row.type.toLowerCase() === 'credit' ? data : '-';
                        }
                    },
                    {
                        data: 'id', name: 'action', render: function (data, type, row) {
                            return ''; // View Details button?
                        }
                    }
                ],
                order: [[0, 'desc']]
            });

            $('#daterange-btn').daterangepicker({
                locale: { format: 'DD/MM/YYYY' },
                autoUpdateInput: false,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            }, function(start, end) {
                $('#date_range_display').text(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#date_range').val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                $('#ledger-table').DataTable().ajax.reload();
                updateExportLinks();
            });

            $('#daterange-btn').on('cancel.daterangepicker', function(ev, picker) {
                $('#date_range_display').text('Select Date Range');
                $('#date_range').val('');
                $('#ledger-table').DataTable().ajax.reload();
                updateExportLinks();
            });

            function updateExportLinks() {
                var dateRange = $('#date_range').val();
                var pdfUrl = '{{ route('ledger.export_pdf', $store->id) }}';
                var excelUrl = '{{ route('ledger.export_excel', $store->id) }}';
                if (dateRange) {
                    pdfUrl += '?date_range=' + encodeURIComponent(dateRange);
                    excelUrl += '?date_range=' + encodeURIComponent(dateRange);
                }
                $('#btn-export-pdf').attr('href', pdfUrl);
                $('#btn-export-excel').attr('href', excelUrl);
            }
        });
    </script>
@endpush