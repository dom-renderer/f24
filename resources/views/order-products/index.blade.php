@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}"/>
<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
@endpush

@section('content')

    <div class="bg-light p-4 rounded">
        <h1> {{ $page_title }} </h1>
        <div class="lead">
            {{ $page_description }}
            @if(auth()->user()->can('order-products.create'))
                <a href="{{ route('order-products.create') }}" class="btn btn-primary btn-sm float-end">Add Product</a>
            @endif
        </div>
        
        <div class="mt-2 mb-3">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <form method="GET" action="{{ route('order-products.index') }}" class="d-flex align-items-center gap-2">
                    <select name="category_id" class="form-select select2" style="width: 250px;">
                        <option value="">Select Category</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}" {{ isset($selectedCategory) && $selectedCategory == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
                    @if(session()->has('order_product_category_filter'))
                        <a href="{{ route('order-products.index', ['reset' => 1]) }}" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Clear</a>
                    @endif
                </form>

                <div class="d-flex gap-2">
                {{-- <a href="{{ route('order-products-price.export') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i> Export Product
                </a>
                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-file-upload me-1"></i> Import Product
                </button> --}}
                </div>
            </div>
            @include('layouts.partials.messages')
        </div>

        {{-- Import Modal --}}
        <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel"><i class="fas fa-file-import me-2"></i>Import Shop Prices</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="alert alert-warning small">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <strong>Important:</strong> Please use the exported file format for importing. Do not rename sheets or change Product/Unit IDs.
                            </div>
                            <div class="mb-3">
                                <label for="importFile" class="form-label fw-bold">Select Excel File (.xlsx, .xls)</label>
                                <input class="form-control" type="file" id="importFile" name="file" accept=".xlsx, .xls" required>
                                <div class="invalid-feedback">Please select a valid Excel file.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="btnImport"><i class="fas fa-upload me-1"></i> Upload & Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped w-100" id="products-table">
                <thead>
                <tr>
                    <th scope="col" width="1%">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">SKU</th>
                    <th scope="col">Category</th>
                    <th scope="col">Status</th>
                    <th scope="col" width="250px">Action</th>
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
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script type="text/javascript">
  $(function () {
    $('.select2').select2({
        placeholder: "Select Category",
        allowClear: true
    });
      
    $.ajaxSetup({
          headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
    });
    
    var table = $('#products-table').DataTable({
        processing: false,
        ordering: false,
        pageLength : 100,
        serverSide: true,
        ajax: "{{ route('order-products.index') }}",
        columns: [
            { data: 'DT_RowIndex', searchable: false },
            {data: 'name', name: 'name'},
            {data: 'sku', name: 'sku'},
            {data: 'category.name', name: 'category.name'},
            {data: 'status', name: 'status'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
    
    $(document).on('click', '.deleteGroup', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you sure you want to delete this product?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $(this).parents('form').submit();
                return true;
            } else {
                return false;
            }
        })
    });

    // Import Form Handler
    $('#importForm').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let fileInput = $('#importFile')[0];
        
        if(fileInput.files.length === 0) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a file.' });
            return;
        }

        let fileName = fileInput.files[0].name;
        let ext = fileName.split('.').pop().toLowerCase();
        if($.inArray(ext, ['xlsx', 'xls']) == -1) {
            Swal.fire({ icon: 'error', title: 'Invalid File', text: 'Please upload an Excel file (.xlsx or .xls).' });
            return;
        }

        const $btn = $('#btnImport');
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Importing...');

        $.ajax({
            url: "{{ route('order-products-price.import') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Upload & Import');
                if(response.status) {
                    $('#importModal').modal('hide');
                    $('#importForm')[0].reset();
                    Swal.fire({ icon: 'success', title: 'Success', text: response.message });
                } else {
                    Swal.fire({ icon: 'error', title: 'Import Failed', text: response.message });
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> Upload & Import');
                let msg = 'Something went wrong.';
                if(xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            }
        });
    });
    
  });
</script>
@endpush
