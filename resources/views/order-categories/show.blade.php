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
                <input value="{{ $category->name }}" 
                    type="text" 
                    class="form-control" 
                    name="name" 
                    id="name" 
                    placeholder="Name" disabled>
                @if ($errors->has('name'))
                    <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                @endif
            </div>

            @if(isset($category->parent->id))
            <div class="mb-3">
                <label for="parent_id" class="form-label">Parent Category</label>
                <select class="form-control select2" name="parent_id" id="parent_id" disabled>
                    <option selected>{{ $category->parent ? $category->parent->name : 'N/A' }}</option>
                </select>
            </div>
            @endif

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" name="description" id="description" placeholder="Description" disabled>{{ $category->description }}</textarea>
                @if ($errors->has('description'))
                    <span class="text-danger text-left">{{ $errors->first('description') }}</span>
                @endif
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-control" disabled>
                    <option value="0" @if(!$category->status) selected @endif>InActive</option>
                    <option value="1" @if($category->status) selected @endif>Active</option>
                </select>
            </div>

            <a href="{{ route('order-categories.index') }}" class="btn btn-default">Back</a>
        </div>

    </div>
@endsection