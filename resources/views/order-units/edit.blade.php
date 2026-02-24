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
            <form method="POST" action="{{ route('order-units.update', $unit->id) }}" id="editUnitForm">
                @method('patch')
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input value="{{ $unit->name }}" 
                        type="text" 
                        class="form-control" 
                        name="name" 
                        id="name" 
                        placeholder="Name" required>
                    @if ($errors->has('name'))
                        <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                    @endif
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" name="description" id="description" placeholder="Description">{{ $unit->description }}</textarea>
                    @if ($errors->has('description'))
                        <span class="text-danger text-left">{{ $errors->first('description') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="0" @if(!$unit->status) selected @endif>InActive</option>
                        <option value="1" @if($unit->status) selected @endif>Active</option>
                    </select>
                </div>

                <a href="{{ route('order-units.index') }}" class="btn btn-default">Back</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>

    </div>
@endsection

@push('js')
<script src="{{ asset('assets/js/other/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $("#editUnitForm").validate({
            rules: {
                name: "required"
            },
            messages: {
                name: "Please enter unit name"
            },
            errorElement: "span",
            errorClass: "text-danger",
            highlight: function(element) {
                $(element).addClass("is-invalid");
            },
            unhighlight: function(element) {
                $(element).removeClass("is-invalid");
            }
        });
    });
</script>
@endpush
