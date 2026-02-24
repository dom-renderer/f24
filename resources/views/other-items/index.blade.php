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
            @if(auth()->user()->can('other-items.create'))
                <a href="{{ route('other-items.create') }}" class="btn btn-primary btn-sm float-end">Add Other Item</a>
            @endif
        </div>
        
        <div class="mt-2">
            @include('layouts.partials.messages')
        </div>

        <div class="table-responsive">
            <table class="table table-striped w-100" id="other-items-table">
                <thead>
                <tr>
                    <th scope="col" width="1%">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Pricing Type</th>
                    <th scope="col">Price Per Piece</th>
                    <th scope="col">Status</th>
                    <th scope="col" width="150px">Action</th>
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
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script src="{{ asset('assets/js/swal.min.js') }}"></script>
<script type="text/javascript">
  $(function () {
      
    $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
    });
    
    var table = $('#other-items-table').DataTable({
        processing: false,
        ordering: false,
        serverSide: true,
        ajax: "{{ route('other-items.index') }}",
        columns: [
            { data: 'DT_RowIndex', searchable: false },
            {data: 'name', name: 'name'},
            {data: 'pricing_type', name: 'pricing_type'},
            {data: 'price_per_piece', name: 'price_per_piece'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    
    $(document).on('click', '.deleteGroup', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you sure you want to delete this item?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).closest('form').submit();
                return true;
            } else {
                return false;
            }
        })
    });
    
  });
</script>
@endpush
