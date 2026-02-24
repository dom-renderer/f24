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
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input value="{{ $instruction->name }}" 
                    type="text" 
                    class="form-control" 
                    name="name" 
                    id="name" 
                    placeholder="Name" disabled>
            </div>

            <a href="{{ route('handling-instructions.index') }}" class="btn btn-default">Back</a>
        </div>

    </div>
@endsection

@push('js')

@endpush
