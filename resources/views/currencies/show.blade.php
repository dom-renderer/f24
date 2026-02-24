@extends('layouts.app-master')

@section('content')
    <div class="bg-light p-4 rounded">
        <h1>View Currency</h1>
        <div class="container mt-4">

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input value="{{ $currency->name }}" 
                    type="text" 
                    class="form-control" 
                    name="name" 
                    placeholder="Currency Name" disabled>
            </div>

            <div class="mb-3">
                <label for="symbol" class="form-label">Symbol</label>
                <input value="{{ $currency->symbol }}" 
                    type="text" 
                    class="form-control" 
                    name="symbol" 
                    placeholder="Currency Symbol (e.g. $, ₹)" disabled>
            </div>

            <a href="{{ route('currencies.index') }}" class="btn btn-default">Back</a>
        </div>

    </div>
@endsection

@push('js')

@endpush
