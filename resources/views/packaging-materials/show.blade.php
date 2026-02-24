@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
@endpush

@section('content')

    <div class="bg-light p-4 rounded">
        <h1> {{ $page_title }} </h1>
        <div class="lead">
            {{ $page_description }}
        </div>
        
        <div class="mt-4">
            <div class="mb-3">
                <label class="form-label fw-bold">Name</label>
                <p>{{ $packagingMaterial->name }}</p>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Pricing Type</label>
                    <p>{{ ucfirst(str_replace('_', ' ', $packagingMaterial->pricing_type)) }}</p>
                </div>
            </div>

            @if($packagingMaterial->pricing_type == 'fixed')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Price Per Piece</label>
                    <p>{{ number_format($packagingMaterial->price_per_piece, 2) }}</p>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Price Includes Tax</label>
                <p>
                    @if($packagingMaterial->price_includes_tax)
                        <span class="badge bg-success">Yes</span>
                    @else
                        <span class="badge bg-secondary">No</span>
                    @endif
                </p>
            </div>

            @if($packagingMaterial->taxSlab)
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Tax Slab</label>
                    <p>{{ $packagingMaterial->taxSlab->name }} ({{ $packagingMaterial->taxSlab->cgst + $packagingMaterial->taxSlab->sgst + $packagingMaterial->taxSlab->igst }}%)</p>
                </div>
            </div>
            @endif
            @endif

            <div class="mb-3">
                <label class="form-label fw-bold">Status</label>
                <p>
                    @if($packagingMaterial->status)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </p>
            </div>

            <div class="mt-4">
                <a href="{{ route('packaging-materials.edit', $packagingMaterial->id) }}" class="btn btn-info">Edit</a>
                <a href="{{ route('packaging-materials.index') }}" class="btn btn-default">Back</a>
            </div>
        </div>

    </div>
@endsection
