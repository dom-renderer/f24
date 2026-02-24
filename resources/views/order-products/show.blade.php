@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
<style>
    .dropzone {
        border: 2px dashed #0087F7;
        border-radius: 5px;
        background: white;
    }
    .select2-container {
        width: 100% !important;
    }
</style>
@endpush

@section('content')

    <div class="bg-light p-4 rounded">
        <h1> {{ $page_title }} </h1>
        <div class="lead">
            {{ $page_description }}
        </div>
        
        <div class="container-fluid mt-4">
                <div class="row">
                    <!-- Left Section -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Basic Information</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name </label>
                                    <input value="{{ $product->name }}" type="text" class="form-control" name="name" id="name" placeholder="Name" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU </label>
                                    <input value="{{ $product->sku }}" type="text" class="form-control" name="sku" id="sku" placeholder="SKU" disabled>
                                </div>

                                <div class="mb-3">
                                    <label for="hsn_code" class="form-label">HSN Code </label>
                                    <input value="{{ $product->hsn_code }}" type="text" class="form-control" name="hsn_code" id="hsn_code" placeholder="HSN Code" disabled>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category </label>
                                    <select class="form-control select2" name="category_id" id="category_id" disabled>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}" {{ $product->category_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status </label><br>
                                    <input type="hidden" name="status" value="0">
                                    <input type="checkbox" name="status" id="status" value="1" {{ $product->status ? 'checked' : '' }} data-toggle="toggle" data-on="Active" data-off="Inactive" data-onstyle="success" data-offstyle="danger" disabled>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="description" placeholder="Description" disabled>{{ $product->description }}</textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Product Images</label>
                                    <div class="needsclick dropzone" id="document-dropzone"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Section -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Product Units
                                <button type="button" class="btn btn-success btn-sm float-end" id="add-row">Add Row</button>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered" id="units-table">
                                    <thead>
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="40%">Unit <span class="text-danger">*</span></th>
                                            <th width="25%">Price <span class="text-danger">*</span></th>
                                            <th width="20%">Status <span class="text-danger">*</span></th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Rows added via JS or Server -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="{{ route('order-products.index') }}" class="btn btn-default">Back</a>
                        </div>
                    </div>
                </div>
        </div>

    </div>
@endsection