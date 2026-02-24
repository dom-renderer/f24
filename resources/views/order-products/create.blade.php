@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
<style>
    .dropzone {
        border: 2px dashed #0087F7;
        border-radius: 5px;
        background: white;
    }
    .select2-container {
        width: 100% !important;
    }
</style>
@endpush

@section('content')

    <div class="bg-light p-4 rounded">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-10">
                    <h1> {{ $page_title }} </h1>
                    <div class="lead">
                        {{ $page_description }}
                    </div>
                </div>
                <div class="col-2">
                    <button type="submit" class="btn btn-primary float-end" style="position: relative;top: 40px;">Save </button>
                </div>
            </div>

            <form method="POST" action="{{ route('order-products.store') }}" id="createProductForm" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <!-- Left Section -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Basic Information</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input value="{{ old('name') }}" type="text" class="form-control" name="name" id="name" placeholder="Name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                    <input value="{{ old('sku') }}" type="text" class="form-control" name="sku" id="sku" placeholder="SKU" required>
                                </div>

                                <div class="mb-3">
                                    <label for="hsn_code" class="form-label">HSN Code <span class="text-danger">*</span></label>
                                    <input value="{{ old('hsn_code') }}" type="text" class="form-control" name="hsn_code" id="hsn_code" placeholder="HSN Code" required>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="category_id" id="category_id" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="tax_slab_id" class="form-label">Tax Slab <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="tax_slab_id" id="tax_slab_id" required>
                                        <option value="">Select Tax Slab</option>
                                        @foreach($taxSlabs as $id => $name)
                                            <option value="{{ $id }}" {{ old('tax_slab_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label><br>
                                    <input type="hidden" name="status" value="0">
                                    <input type="checkbox" name="status" id="status" value="1" checked data-toggle="toggle" data-on="Active" data-off="Inactive" data-onstyle="success" data-offstyle="danger">
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="description" placeholder="Description">{{ old('description') }}</textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Product Images <span class="text-danger">*</span></label>
                                    <div class="needsclick dropzone" id="document-dropzone"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Section -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Default Regular MRP
                                <button type="button" class="btn btn-success btn-sm float-end" id="add-row">Add Row</button>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered" id="units-table">
                                    <thead>
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="40%">Unit <span class="text-danger">*</span></th>
                                            <th width="25%">Price <span class="text-danger">*</span></th>
                                            <th width="20%">Status <span class="text-danger">*</span></th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Rows added via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pricing Tiers Section -->
                        <div id="pricing-tiers-container">
                            @foreach(\App\Models\PricingTier::all() as $tierIndex => $tier)
                                <div class="card mt-3 pricing-tier-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span>{{ $tier->name }}</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3 d-none">
                                            <label class="form-label">Tier Title <span class="text-danger">*</span></label>
                                            <input type="text" value="{{ $tier->name }}" class="form-control" name="pricing_tiers[{{ $tierIndex }}][name]" required readonly placeholder="e.g. Wholesale, VIP">
                                        </div>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Unit</th>
                                                    <th>Price <span class="text-danger">*</span></th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-4">
                            <a href="{{ route('order-products.index') }}" class="btn btn-default">Back</a>
                            <button type="submit" class="btn btn-primary">Save </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>
@endsection

@push('js')
<script src="{{ asset('assets/js/other/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
             dropdownParent: $('body') // or container
        });
        
        // Initial Row
        addRow();

        $("#createProductForm").validate({
            rules: {
                name: "required",
                sku: "required",
                hsn_code: "required",
                category_id: "required",
                tax_slab_id: "required",
                'units[]': {
                    required: true,
                    minlength: 1
                }
            },
            messages: {
                name: "Please enter product name",
                sku: "Please enter SKU",
                hsn_code: "Please enter HSN Code",
                category_id: "Please select a category",
                tax_slab_id: "Please select a tax slab"
            },
            errorElement: "span",
            errorClass: "text-danger",
            errorPlacement: function (error, element) {
                if (element.hasClass("select2-hidden-accessible")) {
                    error.insertAfter(element.next('span.select2'));
                } else if(element.closest('.dropzone').length) {
                    error.insertAfter(element.closest('.dropzone'));
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function(element) {
                $(element).addClass("is-invalid");
            },
            unhighlight: function(element) {
                $(element).removeClass("is-invalid");
            },
            submitHandler: function(form) {
                // Unique Tier Title Check
                if(!checkUniqueTitles()){
                    return false;
                }

                // Check Dropzone files
                if (myDropzone.getAcceptedFiles().length === 0 && myDropzone.getQueuedFiles().length === 0) {
                        // Check if hidden inputs exist (uploaded)
                        if($('input[name="document[]"]').length === 0){
                            Swal.fire('Error', 'Please upload at least one image/document', 'error');
                            return false;
                        }
                }
                
                // Check Units
                if ($('#units-table tbody tr').length === 0) {
                    Swal.fire('Error', 'Please add at least one unit row', 'error');
                    return false;
                }
                
                form.submit();
            }
        });
            
        $('#add-row').click(function(){
            addRow();
        });
        
        $(document).on('click', '.remove-row', function(){
            var row = $(this).closest('tr');
            var rowCount = $('#units-table tbody tr').length;
            
            if(rowCount <= 1){
                 Swal.fire('Warning', 'At least one row is required!', 'warning');
                 return;
            }
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to remove this unit? This will also remove it from all Pricing Tiers.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, remove it!'
            }).then((result) => {
                if (result.isConfirmed) {
                   var uid = row.attr('data-uid');
                   row.remove();
                   resetIndexes();
                   if(uid) syncTiersRemove(uid);
                }
            })
        });

        // Sync Unit Change
        $(document).on('change', '.unit-select', function(){
            var row = $(this).closest('tr');
            var uid = row.attr('data-uid');
            var unitId = $(this).val();
            var unitName = $(this).find('option:selected').text();
            
            if(uid) syncTiersUpdate(uid, unitId, unitName);
        });

        // Unique Title Validation Listener
        $(document).on('change keyup', 'input[name$="[name]"]', function(){
             if($(this).closest('.pricing-tier-card').length > 0){
                 checkUniqueTitles();
             }
        });

        $('#add-tier-btn').click(function(){
            addPricingTier();
        });

        $(document).on('click', '.remove-tier', function(){
            $(this).closest('.card').remove();
            resetTierIndexes();
            checkUniqueTitles();
        });
    });

    function checkUniqueTitles(){
        var titles = [];
        var isValid = true;
        $('.pricing-tier-card input[name$="[name]"]').each(function(){
            var val = $(this).val().trim();
            $(this).removeClass('is-invalid');
            if(val !== ""){
                if(titles.includes(val.toLowerCase())){
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    titles.push(val.toLowerCase());
                }
            }
        });
        
        if(!isValid){
            // Optional: visual cue or toast
        }
        return isValid;
    }

    function addPricingTier() {
        var tierIndex = $('.pricing-tier-card').length;
        
        // Get current units from the Master Price List table
        var units = [];
        $('#units-table tbody tr').each(function(){
            var uid = $(this).attr('data-uid');
            var unitId = $(this).find('.unit-select').val();
            var unitName = $(this).find('.unit-select option:selected').text();
            
             // We add all units, but if not selected show placeholder
            var displayId = unitId ? unitId : '';
            var displayName = (unitId && unitId !== "") ? unitName : 'Select Unit in Master List';
            
            // Generate empty uid if missing (shouldn't happen with new addRow)
            if(!uid) {
                uid = 'created_pre_uid_' + Math.random().toString(36).substr(2, 9);
                $(this).attr('data-uid', uid);
            }

            units.push({id: displayId, name: displayName, uid: uid});
        });

        if($('#units-table tbody tr').length === 0) {
             Swal.fire('Warning', 'Please add at least one unit row in the Master Price List first.', 'warning');
             return;
        }

        var unitsHtml = '';
        units.forEach(function(unit, index){
            unitsHtml += `
                <tr data-linked-uid="${unit.uid}">
                    <td><span class="unit-name-display">${unit.name}</span> <input type="hidden" class="unit-id-input" name="pricing_tiers[${tierIndex}][units][${index}][unit_id]" value="${unit.id}"></td>
                    <td>
                        <input type="number" step="0.01" class="form-control" name="pricing_tiers[${tierIndex}][units][${index}][price]" required min="0" placeholder="Price">
                    </td>
                </tr>
            `;
        });

        var html = `
            <div class="card mt-3 pricing-tier-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Pricing Tier</span>
                    <button type="button" class="btn btn-danger btn-sm remove-tier">Remove Tier</button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tier Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pricing_tiers[${tierIndex}][name]" required readonly placeholder="e.g. Wholesale, VIP">
                    </div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Price <span class="text-danger">*</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${unitsHtml}
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        $('#pricing-tiers-container').append(html);
        checkUniqueTitles();
    }

    function syncTiersAdd(uid) {
        $('.pricing-tier-card').each(function(tierIdx){
             // Calculate Next Unit Index for this Tier
             var unitIdx = $(this).find('table tbody tr').length;
             var tierNameInput = $(this).find('input[name^="pricing_tiers"]');
             var tierInputName = tierNameInput.attr('name'); 
             // Extract tier index from name "pricing_tiers[X][name]"
             var match = tierInputName.match(/pricing_tiers\[(\d+)\]/);
             var currentTierIndex = match ? match[1] : tierIdx;

             var html = `
                <tr data-linked-uid="${uid}">
                    <td><span class="unit-name-display">Select Unit in Master List</span> <input type="hidden" class="unit-id-input" name="pricing_tiers[${currentTierIndex}][units][${unitIdx}][unit_id]" value=""></td>
                    <td>
                        <input type="number" step="0.01" class="form-control" name="pricing_tiers[${currentTierIndex}][units][${unitIdx}][price]" required min="0" placeholder="Price">
                    </td>
                </tr>
            `;
            $(this).find('table tbody').append(html);
        });
    }

    function syncTiersRemove(uid){
        $('.pricing-tier-card table tbody tr[data-linked-uid="'+uid+'"]').remove();
        // Reset indexes inside tiers to ensure array continuity (optional but good)
        // resetTierIndexes handles unit indexes
        resetTierIndexes();
    }

    function syncTiersUpdate(uid, unitId, unitName){
        var rows = $('.pricing-tier-card table tbody tr[data-linked-uid="'+uid+'"]');
        rows.find('.unit-name-display').text(unitName);
        rows.find('.unit-id-input').val(unitId);
    }

    function resetTierIndexes() {
        $('.pricing-tier-card').each(function(index){
            // Update name inputs
            $(this).find('input[name^="pricing_tiers"]').each(function(){
                var name = $(this).attr('name');
                if(name.indexOf('[units]') === -1){ // Tier Name
                     var newName = name.replace(/pricing_tiers\[\d+\]/, 'pricing_tiers[' + index + ']');
                     $(this).attr('name', newName);
                }
            });
            
             // Update unit indexes within tier
            $(this).find('table tbody tr').each(function(uIndex){
                $(this).find('input[name^="pricing_tiers"]').each(function(){
                    var nm = $(this).attr('name');
                     // Replace tier index first
                     nm = nm.replace(/pricing_tiers\[\d+\]/, 'pricing_tiers[' + index + ']');
                     // Replace unit index: [units][OLD] -> [units][NEW]
                     var newNm = nm.replace(/units\[\d+\]/, 'units[' + uIndex + ']');
                     $(this).attr('name', newNm);
                });
            });
        });
    }

    function addRow() {
        var index = $('#units-table tbody tr').length;
        var uid = 'row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        var html = `
            <tr data-uid="${uid}">
                <td class="index">${index + 1}</td>
                <td>
                    <select class="form-control unit-select" name="units[${index}][unit_id]" required>
                        <option value="">Select Unit</option>
                        @foreach($units as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control" name="units[${index}][price]" required min="0" placeholder="Price">
                </td>
                <td>
                     <input type="hidden" name="units[${index}][status]" value="0">
                     <input type="checkbox" name="units[${index}][status]" value="1" checked class="status-toggle" data-toggle="toggle" data-on="Active" data-off="Inactive" data-onstyle="success" data-offstyle="danger" data-size="mini">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
                </td>
            </tr>
        `;
        
        $('#units-table tbody').append(html);
        
        // Init Select2 and Toggle for new elements
        var newRow = $('#units-table tbody tr').last();
        newRow.find('.unit-select').select2();
        newRow.find('.status-toggle').bootstrapToggle();

        syncTiersAdd(uid);
        
        function resetIndexes(){
            $('#units-table tbody tr').each(function(index){
                $(this).find('.index').text(index + 1);
                $(this).find('select, input').each(function(){
                    var name = $(this).attr('name');
                    if(name){
                        var newName = name.replace(/units\[\d+\]/, 'units[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        }

    };

    var uploadedDocumentMap = {}
    var myDropzone; // expose for validation check
    
    Dropzone.options.documentDropzone = {
        url: '{{ route('order-products.upload-image') }}',
        maxFilesize: 2, // MB
        addRemoveLinks: true,
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        success: function (file, response) {
            $('form').append('<input type="hidden" name="document[]" value="' + response.name + '">')
            uploadedDocumentMap[file.name] = response.name
        },
        removedfile: function (file) {
            file.previewElement.remove()
            var name = ''
            if (typeof file.file_name !== 'undefined') {
                name = file.file_name
            } else {
                name = uploadedDocumentMap[file.name]
            }
            $('form').find('input[name="document[]"][value="' + name + '"]').remove()
            
             $.ajax({
                type: 'POST',
                url: '{{ route('order-products.delete-image') }}',
                data: {filename: name, _token: '{{ csrf_token() }}'},
                success: function (data) {},
                error: function (e) {}
            });
        },
        init: function () {
            myDropzone = this;
        }
    }
</script>
@endpush
