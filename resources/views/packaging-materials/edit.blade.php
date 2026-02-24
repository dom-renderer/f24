@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/select2.min.css') }}"/>
@endpush

@section('content')

    <div class="bg-light p-4 rounded">
        <h1> {{ $page_title }} </h1>
        <div class="lead">
            {{ $page_description }}
        </div>
        
        <div class="mt-4">
            <form method="POST" action="{{ route('packaging-materials.update', $packagingMaterial->id) }}" id="editPackagingMaterialForm">
                @method('patch')
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input value="{{ old('name', $packagingMaterial->name) }}" 
                        type="text" 
                        class="form-control" 
                        name="name" 
                        id="name" 
                        placeholder="Name" required>
                    @if ($errors->has('name'))
                        <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                    @endif
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pricing_type" class="form-label">Pricing Type <span class="text-danger">*</span></label>
                        <select name="pricing_type" id="pricing_type" class="form-control" required>
                            <option value="">Select Pricing Type</option>
                            <option value="fixed" {{ old('pricing_type', $packagingMaterial->pricing_type) == 'fixed' ? 'selected' : '' }}>Fixed</option>
                            <option value="as_per_actual" {{ old('pricing_type', $packagingMaterial->pricing_type) == 'as_per_actual' ? 'selected' : '' }}>As Per Actual</option>
                        </select>
                        @if ($errors->has('pricing_type'))
                            <span class="text-danger text-left">{{ $errors->first('pricing_type') }}</span>
                        @endif
                    </div>
                </div>

                <div id="fixed_price_section">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="price_per_piece" class="form-label">Price Per Piece <span class="text-danger">*</span></label>
                            <input value="{{ old('price_per_piece', $packagingMaterial->price_per_piece) }}" 
                                type="number" 
                                step="0.01"
                                min="0"
                                class="form-control" 
                                name="price_per_piece" 
                                id="price_per_piece" 
                                placeholder="0.00">
                            @if ($errors->has('price_per_piece'))
                                <span class="text-danger text-left">{{ $errors->first('price_per_piece') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="price_includes_tax" value="1" id="price_includes_tax" {{ old('price_includes_tax', $packagingMaterial->price_includes_tax) ? 'checked' : '' }}>
                            <label class="form-check-label" for="price_includes_tax">
                                Price Includes Tax
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row" id="tax_slab_section">
                    <div class="col-md-6 mb-3">
                        <label for="tax_slab_id" class="form-label">Tax Slab <span class="text-danger">*</span></label>
                        <select name="tax_slab_id" id="tax_slab_id" class="form-control select2" required>
                            <option value="">Select Tax Slab</option>
                            @foreach($taxSlabs as $slab)
                                <option value="{{ $slab->id }}" {{ old('tax_slab_id', $packagingMaterial->tax_slab_id) == $slab->id ? 'selected' : '' }}>{{ $slab->name }}</option>
                            @endforeach
                        </select>
                            @if ($errors->has('tax_slab_id'))
                            <span class="text-danger text-left">{{ $errors->first('tax_slab_id') }}</span>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="1" {{ $packagingMaterial->status == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ $packagingMaterial->status == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <a href="{{ route('packaging-materials.index') }}" class="btn btn-default">Back</a>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>

    </div>
@endsection

@push('js')
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });

        $("#editPackagingMaterialForm").validate({
            ignore: ":hidden",
            rules: {
                name: "required",
                pricing_type: "required",
                price_per_piece: {
                    required: function(element) {
                        return $("#pricing_type").val() == "fixed";
                    },
                    number: true,
                    min: 0
                },
                tax_slab_id: {
                    required: true
                }
            },
            messages: {
                name: "Please enter item name",
                pricing_type: "Please select pricing type",
                price_per_piece: {
                    required: "Please enter price per piece",
                    number: "Please enter a valid number",
                    min: "Price cannot be negative"
                },
                tax_slab_id: "Please select a tax slab"
            },
            errorElement: "span",
            errorClass: "text-danger",
            highlight: function(element) {
                $(element).addClass("is-invalid");
                if ($(element).hasClass('select2-hidden-accessible')) {
                     $(element).next('.select2-container').addClass('is-invalid');
                }
            },
            unhighlight: function(element) {
                $(element).removeClass("is-invalid");
                if ($(element).hasClass('select2-hidden-accessible')) {
                     $(element).next('.select2-container').removeClass('is-invalid');
                }
            },
             errorPlacement: function (error, element) {
                if (element.hasClass('select2-hidden-accessible')) {
                    error.insertAfter(element.next('.select2-container'));
                } else {
                    error.insertAfter(element);
                }
            }
        });

        $('#tax_slab_id').on('change', function() {
            $(this).valid();
        });
    });
</script>
@endpush
