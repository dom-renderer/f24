@extends('layouts.app-master')

@push('css')
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
    <div class="bg-light p-4 rounded">
        <h1>{{ $page_title }}</h1>
        <div class="lead">
            Add new vehicle.
        </div>

        <div class=" mt-4">
            <form method="POST" action="{{ route('vehicles.store') }}" id="vehicleForm">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input value="{{ old('name') }}" type="text" class="form-control" name="name" placeholder="Name" required>
                    @if ($errors->has('name'))
                        <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="make" class="form-label">Make</label>
                    <input value="{{ old('make') }}" type="text" class="form-control" name="make" placeholder="Make" required>
                    @if ($errors->has('make'))
                        <span class="text-danger text-left">{{ $errors->first('make') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="number" class="form-label">Number</label>
                    <input value="{{ old('number') }}" type="text" class="form-control" name="number" placeholder="Vehicle Number" required>
                    @if ($errors->has('number'))
                        <span class="text-danger text-left">{{ $errors->first('number') }}</span>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('vehicles.index') }}" class="btn btn-default">Back</a>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $("#vehicleForm").validate({
                rules: {
                    name: "required",
                    make: "required",
                    number: "required"
                },
                messages: {
                    name: "Please enter vehicle name",
                    make: "Please enter vehicle make",
                    number: "Please enter vehicle number"
                }
            });
        });
    </script>
@endpush
