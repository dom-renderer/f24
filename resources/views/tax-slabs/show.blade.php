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
                <p>{{ $taxSlab->name }}</p>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">CGST (%)</label>
                    <p>{{ $taxSlab->cgst }}%</p>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">SGST (%)</label>
                    <p>{{ $taxSlab->sgst }}%</p>
                </div>
                {{-- <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">IGST (%)</label>
                    <p>{{ $taxSlab->igst }}%</p>
                </div> --}}
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Status</label>
                <p>
                    @if($taxSlab->status)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </p>
            </div>

            <div class="mt-4">
                <a href="{{ route('tax-slabs.edit', $taxSlab->id) }}" class="btn btn-info">Edit</a>
                <a href="{{ route('tax-slabs.index') }}" class="btn btn-default">Back</a>
            </div>
        </div>

    </div>
@endsection
