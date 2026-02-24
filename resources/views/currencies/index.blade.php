@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}"/>
@endpush

@section('content')

    <div class="bg-light p-4 rounded">
        <h1> {{ $page_title }} </h1>
        <div class="lead">
            {{ $page_description }}
            @if(auth()->user()->can('currencies.create'))
                <a href="{{ route('currencies.create') }}" class="btn btn-primary btn-sm float-end">Add Currency</a>
            @endif
        </div>
        
        <div class="mt-2">
            @include('layouts.partials.messages')
        </div>

        <div class="table-responsive">
            <table class="table table-striped" id="currencies-table" cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th scope="col" width="1%">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Symbol</th>
                    <th scope="col" width="10%">Default</th>
                    <th scope="col" width="10%">Action</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div> 

@endsection

@push('js')
<script src="{{ asset('assets/js/other/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/other/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/js/other/dataTables.bootstrap5.min.js') }}"></script>

<script>
    $(document).ready(function() {
        var table = $('#currencies-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('currencies.index') }}",
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'symbol', name: 'symbol'},
                {data: 'is_default', name: 'is_default', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });

        // Default Currency Change Handler
        $(document).on('change', '.default-currency-checkbox', function() {
            var id = $(this).data('id');
            var isChecked = $(this).is(':checked');

            if (!isChecked) {
                // Prevent unchecking the default currency manually
                $(this).prop('checked', true);
                Swal.fire('Warning', 'One currency must always be default.', 'warning');
                return;
            }

            $.ajax({
                url: "{{ url('currencies/set-default') }}/" + id,
                type: 'POST',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Default currency updated.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    table.ajax.reload(null, false); // Reload table without jumping page
                },
                error: function(err) {
                    Swal.fire('Error!', 'Something went wrong.', 'error');
                    table.ajax.reload(null, false);
                }
            });
        });
    });

    function deleteCurrency(id) {
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
                    url: "{{ url('currencies') }}/" + id,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            'Currency has been deleted.',
                            'success'
                        );
                        $('#currencies-table').DataTable().ajax.reload();
                    },
                    error: function(err) {
                        Swal.fire(
                            'Error!',
                            'Something went wrong.',
                            'error'
                        );
                    }
                });
            }
        });
    }
</script>
@endpush
