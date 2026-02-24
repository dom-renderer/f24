@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
@endpush

@section('content')
<div class="bg-light p-4 rounded">
    <h1> {{ $page_title }} </h1>
        <div class="lead">
            {{ $page_description }}
            @if(auth()->user()->can('grievance-reporting.create'))
                <a href="{{ route('grievance-reporting.create') }}" class="btn btn-primary btn-sm float-end">Report Grievance</a>
            @endif
        </div>

        <div class="mt-2">
            @include('layouts.partials.messages')
        </div>

        <div class="table-responsive">
            <table class="table table-striped w-100" id="grievances-table">
                <thead>
                <tr>
                    <th width="1%">#</th>
                    <th>Order #</th>
                    <th>Reported By</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th width="200px">Action</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>

</div>
@endsection

@push('js')
<script src="{{ asset('assets/js/other/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/other/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var table = $('#grievances-table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        ajax: "{{ route('grievance-reporting.index') }}",
        columns: [
            { data: 'DT_RowIndex', searchable: false },
            { data: 'order_number', name: 'order_number' },
            { data: 'reported_by_name', name: 'reported_by_name', orderable: false },
            { data: 'status_label', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
    
    // Status Change
    $(document).on('change', '.status-select', function() {
        const $select = $(this);
        const grievanceId = $select.data('id');
        const newStatus = $select.val();
        const oldStatus = $select.attr('data-oldstatus');

        Swal.fire({
            title: 'Change Status?',
            text: "Are you sure you want to change the grievance status?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, change it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('grievance-reporting.update-status') }}",
                    type: 'POST',
                    data: { id: grievanceId, status: newStatus },
                    success: function(response) {
                        if (response.status) {
                            Swal.fire('Success', response.message, 'success');
                            $select.attr('data-oldstatus', newStatus);
                        } else {
                            Swal.fire('Error', response.message, 'error');
                            $select.val(oldStatus);
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Internal Server Error', 'error');
                        $select.val(oldStatus);
                    }
                });
            } else {
                $select.val(oldStatus);
            }
        });
    });

    window.deleteGrievance = function(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/grievance-reporting/' + id,
                    type: 'DELETE',
                    success: function(response) {
                        if(response.status) {
                            Swal.fire('Deleted!', response.message, 'success');
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    }
                });
            }
        });
    };
});
</script>
@endpush
