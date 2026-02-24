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
                    <button type="submit" class="btn btn-primary float-end" style="position: relative;top: 40px;"> Update </button>
                </div>
            </div>

            <form method="POST" action="{{ route('order-products.update', $product->id) }}" id="editProductForm" enctype="multipart/form-data">
                @method('patch')
                @csrf
                <div class="row">
                    <!-- Left Section -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">Basic Information</div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input value="{{ $product->name }}" type="text" class="form-control" name="name" id="name" placeholder="Name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                    <input value="{{ $product->sku }}" type="text" class="form-control" name="sku" id="sku" placeholder="SKU" required>
                                </div>

                                <div class="mb-3">
                                    <label for="hsn_code" class="form-label">HSN Code <span class="text-danger">*</span></label>
                                    <input value="{{ $product->hsn_code }}" type="text" class="form-control" name="hsn_code" id="hsn_code" placeholder="HSN Code" required>
                                </div>

                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="category_id" id="category_id" required>
                                        <option value="">Select Category</option>
                                        @foreach($categories as $id => $name)
                                            <option value="{{ $id }}" {{ $product->category_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="tax_slab_id" class="form-label">Tax Slab <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="tax_slab_id" id="tax_slab_id" required>
                                        <option value="">Select Tax Slab</option>
                                        @foreach($taxSlabs as $id => $name)
                                            <option value="{{ $id }}" {{ $product->tax_slab_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label><br>
                                    <input type="hidden" name="status" value="0">
                                    <input type="checkbox" name="status" id="status" value="1" {{ $product->status ? 'checked' : '' }} data-toggle="toggle" data-on="Active" data-off="Inactive" data-onstyle="success" data-offstyle="danger">
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="description" placeholder="Description">{{ $product->description }}</textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Product Images</label>
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
                                        <!-- Rows added via JS or Server -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div id="pricing-tiers-container">
                            @foreach(\App\Models\PricingTier::all() as $tierIndex => $tier)
                                <div class="card mt-3 pricing-tier-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span>{{ $tier->name }}</span>
                                        {{-- <button type="button" class="btn btn-danger btn-sm remove-tier">Remove Tier</button> --}}
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
                                                @php
                                                    $tierPrices = $product->unitPriceTiers->where('pricing_tier_id', $tier->id);
                                                @endphp
                                                @forelse($tierPrices as $unitIndex => $tierPrice)
                                                    <tr data-linked-uid="existing_{{ $tierPrice->product_unit_id }}">
                                                        <td>
                                                            <span class="unit-name-display">{{ $tierPrice->unit->name ?? 'N/A' }}</span>
                                                            <input type="hidden" class="unit-id-input" name="pricing_tiers[{{ $tierIndex }}][units][{{ $unitIndex }}][unit_id]" value="{{ $tierPrice->product_unit_id }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01" value="{{ $tierPrice->amount }}" class="form-control" name="pricing_tiers[{{ $tierIndex }}][units][{{ $unitIndex }}][price]" required min="0" placeholder="Price">
                                                        </td>
                                                    </tr>
                                                @empty
                                                    @if(!empty($product->units) && is_iterable($product->units))
                                                        @foreach ($product->units as $In => $pu)
                                                            <tr data-uid="row_{{ time() }}_{{ uniqid() }}">
                                                                <td>
                                                                    <span class="unit-name-display">{{ $pu->unit->name ?? 'N/A' }}</span>
                                                                    <input type="hidden" class="unit-id-input" name="pricing_tiers[{{ $tierIndex }}][units][{{ $In }}][unit_id]" value="{{ $pu->unit_id }}">
                                                                </td>
                                                                <td>
                                                                    <input type="number" step="0.01" value="{{ $pu->price }}" class="form-control" name="pricing_tiers[{{ $tierIndex }}][units][{{ $In }}][price]" required min="0" placeholder="Price">
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('order-products.index') }}" class="btn btn-default">Back</a>
                            <button type="submit" class="btn btn-primary">Update </button>
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
        $('.select2').select2({ dropdownParent: $('body') });
        
        // Populate existing units
        var existingUnits = {!! json_encode($product->units) !!};
        if(existingUnits.length > 0){
            existingUnits.forEach(function(unit){
                addRow(unit);
            });
        } else {
            addRow(); // Add one empty row if none
        }

        $("#editProductForm").validate({
            rules: {
                name: "required",
                sku: "required",
                hsn_code: "required",
                category_id: "required",
                tax_slab_id: "required"
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
                 // Check Unique Titles
                 if(!checkUniqueTitles()){
                    return false;
                 }

                 // Check Units (Validation rule 'units[]' might assume name="units[]" but we have units[0][unit_id]...)
                 // jQuery Validate complex array names can be tricky. Use manual check.
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

        $('#add-tier-btn').click(function(){
            addPricingTier();
        });

        $(document).on('click', '.remove-tier', function(){
            $(this).closest('.card').remove();
            resetTierIndexes();
            checkUniqueTitles();
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

    });






    
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

    var uploadedDocumentMap = {}
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
            
             // Delete from temp or db logic here (as before)
             // ...
             // For Edit, handling persisted deletion is key.
             if(file.id){
                 $.ajax({
                    type: 'POST',
                    url: '{{ route('order-products.delete-image') }}',
                    data: {id: file.id, _token: '{{ csrf_token() }}'},
                    success: function (data) {},
                    error: function (e) {}
                });
             } else if(name) {
                 $.ajax({
                    type: 'POST',
                    url: '{{ route('order-products.delete-image') }}',
                    data: {filename: name, _token: '{{ csrf_token() }}'},
                    success: function (data) {},
                    error: function (e) {}
                });
             }
        },
        init: function () {
            @if(isset($product) && $product->images)
                var files = {!! json_encode($product->images) !!}
                for (var i in files) {
                    var file = files[i]
                    var mockFile = { name: file.image_path, size: 12345, id: file.id }; 
                    
                    this.emit("addedfile", mockFile);
                    this.emit("thumbnail", mockFile, '{{ asset('storage/order-product-images') }}/' + file.image_path); 
                    this.emit("complete", mockFile);
                }
            @endif
        }
    }
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
        return isValid;
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

    function addPricingTier() {
        var tierIndex = $('.pricing-tier-card').length;
        
        var units = [];
        $('#units-table tbody tr').each(function(){
            var uid = $(this).attr('data-uid');
            var unitId = $(this).find('.unit-select').val();
            var unitName = $(this).find('.unit-select option:selected').text();
            
            // Generate empty uid if missing (shouldn't happen with new addRow)
            if(!uid) {
                uid = 'temp_uid_' + Math.random().toString(36).substr(2, 9);
                $(this).attr('data-uid', uid);
            }
            
            var displayId = unitId ? unitId : '';
            var displayName = (unitId && unitId !== "") ? unitName : 'Select Unit in Master List';
            
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

    function addRow(data = null) {
        var index = $('#units-table tbody tr').length;
        var unitId = data ? data.unit_id : '';
        var price = data ? data.price : '';
        var status = data ? data.status : 1;
        var checked = status == 1 ? 'checked' : '';
        
        var uid = 'row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        
        var html = `
            <tr data-uid="${uid}">
                <td class="index">${index + 1}</td>
                <td>
                    <select class="form-control unit-select" name="units[${index}][unit_id]" required>
                        <option value="">Select Unit</option>
                        @foreach($units as $id => $name)
                            <option value="{{ $id }}" ${unitId == "{{ $id }}" ? 'selected' : '' }>{{ $name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control" name="units[${index}][price]" value="${price}" required min="0" placeholder="Price">
                </td>
                <td>
                     <input type="hidden" name="units[${index}][status]" value="0">
                     <input type="checkbox" name="units[${index}][status]" value="1" ${checked} class="status-toggle" data-toggle="toggle" data-on="Active" data-off="Inactive" data-onstyle="success" data-offstyle="danger" data-size="mini">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row">X</button>
                </td>
            </tr>
        `;
        
        $('#units-table tbody').append(html);
        
        var newRow = $('#units-table tbody tr').last();
        newRow.find('.unit-select').select2({ dropdownParent: $('body') });
        newRow.find('.status-toggle').bootstrapToggle();

        if(data) {
            var selectedName = newRow.find('.unit-select option[value="'+unitId+'"]').text();
            linkExistingTiers(uid, unitId, selectedName);
        } else {
            syncTiersAdd(uid);
        }
    }

    function syncTiersAdd(uid) {
        $('.pricing-tier-card').each(function(tierIdx){
             var unitIdx = $(this).find('table tbody tr').length;
             var tierNameInput = $(this).find('input[name^="pricing_tiers"]');
             var tierInputName = tierNameInput.attr('name'); 
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
        resetTierIndexes(); 
    }

    function syncTiersUpdate(uid, unitId, unitName){
        var rows = $('.pricing-tier-card table tbody tr[data-linked-uid="'+uid+'"]');
        rows.find('.unit-name-display').text(unitName);
        rows.find('.unit-id-input').val(unitId);
    }
    
    function linkExistingTiers(uid, unitId, unitName) {
        if(!unitId) return;
        $('.pricing-tier-card table tbody tr').each(function(){
            var input = $(this).find('input[type="hidden"][name*="[unit_id]"]');
            if(input.val() == unitId){
                $(this).attr('data-linked-uid', uid);
                input.addClass('unit-id-input'); 
                
                var td = $(this).find('td:first');
                if(td.find('.unit-name-display').length === 0){
                   var inputEl = td.find('input'); 
                   td.html(`<span class="unit-name-display">${unitName}</span>`);
                   td.append(inputEl);
                }
            }
        });
    }
</script>
@endpush
