@extends('layouts.app-master')

@push('css')
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <div class="bg-light p-4 rounded">
        <h1>{{ $page_title }}</h1>
        <div class="lead">
            Edit vehicle.
        </div>

        <div class=" mt-4">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input value="{{ $vehicle->name }}" type="text" class="form-control" name="name" placeholder="Name" disabled>
                    @if ($errors->has('name'))
                        <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="make" class="form-label">Make</label>
                    <input value="{{ $vehicle->make }}" type="text" class="form-control" name="make" placeholder="Make" disabled>
                    @if ($errors->has('make'))
                        <span class="text-danger text-left">{{ $errors->first('make') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="number" class="form-label">Number</label>
                    <input value="{{ $vehicle->number }}" type="text" class="form-control" name="number" placeholder="Vehicle Number" disabled>
                    @if ($errors->has('number'))
                        <span class="text-danger text-left">{{ $errors->first('number') }}</span>
                    @endif
                </div>

                <a href="{{ route('vehicles.index') }}" class="btn btn-default">Back</a>
        </div>
    </div>
@endsection

@push('js')

@endpush
