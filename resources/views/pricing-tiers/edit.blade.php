@extends('layouts.app-master')

@push('css')
@endpush

@section('content')
    <form method="POST" action="{{ route('pricing-tiers.update', $pricingTier->id) }}" id="pricing-tier-form">
        @csrf
        @method('PATCH')
        <div class="bg-light p-4 rounded row">
            <div class="col-12">
                <h3>Edit Pricing Tier</h3>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input value="{{ old('name', $pricingTier->name) }}" type="text" class="form-control" name="name" placeholder="Name" required>
                    @if ($errors->has('name'))
                        <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" name="description" placeholder="Description">{{ old('description', $pricingTier->description) }}</textarea>
                    @if ($errors->has('description'))
                        <span class="text-danger text-left">{{ $errors->first('description') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" name="status" id="status" required>
                        <option value="1" {{ old('status', $pricingTier->status) == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $pricingTier->status) == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @if ($errors->has('status'))
                        <span class="text-danger text-left">{{ $errors->first('status') }}</span>
                    @endif
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('pricing-tiers.index') }}" class="btn btn-default">Back</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#pricing-tier-form').validate({
                rules: {
                    name: {
                        required: true,
                    },
                    status: {
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: "Please enter name",
                    },
                    status: {
                        required: "Please select status"
                    }
                },
                errorElement: "span",
                errorClass: "text-danger"
            });
        });
    </script>
@endpush
