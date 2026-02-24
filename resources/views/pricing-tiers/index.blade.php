@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}"/>
@endpush

@section('content')

    <div class="bg-light p-4 rounded">
        <h1> Pricing Tiers </h1>
        <div class="lead">
            Manage pricing tiers here
            <a href="{{ route('pricing-tiers.create') }}" class="btn btn-primary btn-sm float-end">Add Pricing Tier</a>
        </div>
        
        <div class="mt-2">
            @include('layouts.partials.messages')
        </div>

        <div class="table-responsive">
            <table class="table table-striped fdf" id="pricing-tiers-table" cellspacing="0" width="100%">
                <thead>
                <tr>
                    <th scope="col" width="1%">#</th>
                    <th scope="col">Name</th>
                    <th scope="col">Description</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
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
    
    $(document).ready(function($){

        let table = new DataTable('#pricing-tiers-table', {
            dom: '<"d-flex justify-content-between mb-2"lf>rt<"d-flex flex-column float-start mt-3"pi><"clear">',
            pageLength: 50,
            lengthMenu: [ [10, 25, 50, 100], [10, 25, 50, 100] ],
            ajax: {
                url: "{{ route('pricing-tiers.index') }}",
            },
            processing: false,
            ordering: false,
            serverSide: true,
            columns: [
                { data: 'DT_RowIndex', searchable: false },
                 { data: 'name' },
                 { data: 'description' },
                 { data: 'status' },
                 { data: 'action',
                    searchable: false,
                    orderable: false
                 }
            ]
        });
        
    });
 </script>  
@endpush
