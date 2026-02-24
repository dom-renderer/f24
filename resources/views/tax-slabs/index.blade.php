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
            @if(auth()->user()->can('tax-slabs.create'))
                <a href="{{ route('tax-slabs.create') }}" class="btn btn-primary btn-sm float-end">Add Tax Slab</a>
            @endif
        </div>
        
        <div class="mt-2">
            @include('layouts.partials.messages')
        </div>

        <div class="table-responsive">
            <table class="table table-striped w-100" id="tax-slabs-table">
                <thead>
                <tr>
                    <th scope="col" width="1%">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">CGST</th>
                    <th scope="col">SGST</th>
                    {{-- <th scope="col">IGST</th> --}}
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
    
    var table = $('#tax-slabs-table').DataTable({
        processing: false,
        ordering: false,
        serverSide: true,
        ajax: "{{ route('tax-slabs.index') }}",
        columns: [
            { data: 'DT_RowIndex', searchable: false },
            {data: 'name', name: 'name'},
            {data: 'cgst', name: 'cgst'},
            {data: 'sgst', name: 'sgst'},
            // {data: 'igst', name: 'igst'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    
    $(document).on('click', '.deleteGroup', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you sure you want to delete this tax slab?',
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
