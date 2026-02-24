@extends('layouts.app-master')

@push('css')
@endpush

@section('content')
    <div class="bg-light p-4 rounded row">
        <div class="col-12">
            <h3>Edit Pricing Tier</h3>
            
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input value="{{ old('name', $pricingTier->name) }}" type="text" class="form-control" name="name" placeholder="Name" disabled>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" name="description" placeholder="Description" disabled>{{ old('description', $pricingTier->description) }}</textarea>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-control" name="status" id="status" disabled>
                    <option value="1" {{ old('status', $pricingTier->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $pricingTier->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('pricing-tiers.index') }}" class="btn btn-default">Back</a>
            </div>
        </div>
    </div>
@endsection

@push('js')

@endpush
