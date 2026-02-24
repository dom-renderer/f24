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
        
        <div class=" mt-4">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input value="{{ $unit->name }}" 
                    type="text" 
                    class="form-control" 
                    name="name" 
                    id="name" 
                    placeholder="Name" required disabled>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" name="description" id="description" placeholder="Description" disabled>{{ $unit->description }}</textarea>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-control" disabled>
                    <option value="0" @if(!$unit->status) selected @endif>InActive</option>
                    <option value="1" @if($unit->status) selected @endif>Active</option>
                </select>
            </div>

            <a href="{{ route('order-units.index') }}" class="btn btn-default">Back</a>
        </div>

    </div>
@endsection