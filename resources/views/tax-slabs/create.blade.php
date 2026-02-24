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
            <form method="POST" action="{{ route('tax-slabs.store') }}" id="createTaxSlabForm">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input value="{{ old('name') }}" 
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
                        <label for="cgst" class="form-label">CGST (%) <span class="text-danger">*</span></label>
                        <input value="{{ old('cgst') }}" 
                            type="number" 
                            step="0.01"
                            min="0" max="100"
                            class="form-control" 
                            name="cgst" 
                            id="cgst" 
                            placeholder="0.00" required>
                        @if ($errors->has('cgst'))
                            <span class="text-danger text-left">{{ $errors->first('cgst') }}</span>
                        @endif
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="sgst" class="form-label">SGST (%) <span class="text-danger">*</span></label>
                        <input value="{{ old('sgst') }}" 
                            type="number" 
                            step="0.01"
                            min="0" max="100"
                            class="form-control" 
                            name="sgst" 
                            id="sgst" 
                            placeholder="0.00" required>
                        @if ($errors->has('sgst'))
                            <span class="text-danger text-left">{{ $errors->first('sgst') }}</span>
                        @endif
                    </div>
                    {{-- <div class="col-md-4 mb-3">
                        <label for="igst" class="form-label">IGST (%) <span class="text-danger">*</span></label>
                        <input value="{{ old('igst') }}" 
                            type="number" 
                            step="0.01"
                            min="0" max="100"
                            class="form-control" 
                            name="igst" 
                            id="igst" 
                            placeholder="0.00" required>
                        @if ($errors->has('igst'))
                            <span class="text-danger text-left">{{ $errors->first('igst') }}</span>
                        @endif
                    </div> --}}
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <a href="{{ route('tax-slabs.index') }}" class="btn btn-default">Back</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>

    </div>
@endsection

@push('js')
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $.validator.addMethod("checkTotalTax", function(value, element) {
            var cgst = parseFloat($('#cgst').val()) || 0;
            var sgst = parseFloat($('#sgst').val()) || 0;
            return (cgst + sgst) <= 100;
        }, "Total of CGST and SGST must be less than or equal to 100.");

        $("#createTaxSlabForm").validate({
            rules: {
                name: "required",
                cgst: {
                    required: true,
                    number: true,
                    min: 0,
                    max: 100,
                    checkTotalTax: true
                },
                sgst: {
                    required: true,
                    number: true,
                    min: 0,
                    max: 100,
                    checkTotalTax: true
                }
                // igst: {
                //     required: true,
                //     number: true,
                //     min: 0,
                //     max: 100
                // }
            },
            messages: {
                name: "Please enter tax slab name",
                cgst: {
                    required: "Please enter CGST",
                    number: "Please enter a valid number",
                    min: "Value must be greater than or equal to 0",
                    max: "Value must be less than or equal to 100"
                },
                sgst: {
                    required: "Please enter SGST",
                    number: "Please enter a valid number",
                    min: "Value must be greater than or equal to 0",
                    max: "Value must be less than or equal to 100"
                }
                // igst: {
                //     required: "Please enter IGST",
                //     number: "Please enter a valid number",
                //     min: "Value must be greater than or equal to 0",
                //     max: "Value must be less than or equal to 100"
                // }
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

        // Re-validate on change to ensure sum check runs
        $('#cgst, #sgst').on('change keyup', function() {
             var validator = $("#createTaxSlabForm").validate();
             if($('#cgst').val() !== "" && $('#sgst').val() !== "") {
                 validator.element("#cgst");
                 validator.element("#sgst");
             }
        });
    });
</script>
@endpush
