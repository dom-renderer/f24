@extends('layouts.app-master')

@push('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/datatable-bootstrap.css') }}" />
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        .filter-bar {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .filter-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #6b7280;
            margin-bottom: 0.5rem;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
    </style>
@endpush

@section('content')
    <div class="bg-light p-4 rounded">
        <h1> {{ $page_title }} </h1>
        <div class="lead">
            {{ $page_description }}
            @if (auth()->user()->can('stores.create'))
                <a href="{{ route('stores.create') }}" class="btn btn-primary btn-sm float-end">Add Store</a>
            @endif

            @if (auth()->user()->can('stores.import'))
                <button data-bs-toggle="modal"
                    data-bs-target="#browser-file" class="btn btn-success btn-sm float-end"
                    style="margin-right:10px;">Import</button>
            @endif

            @if (auth()->user()->can('stores.export'))
                <button class="btn btn-success btn-sm float-end" class="btn btn-success"  id="export-stores" style="margin-right:10px;">Export</button>
            @endif
        </div>

        <div class="mt-2">
            @include('layouts.partials.messages')
        </div>

        {{-- Filter Bar --}}
        <div class="filter-bar">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="filter-label">Search</label>
                    <input type="text" id="filter-general-search" class="form-control" placeholder="Search">
                </div>
                <div class="col-md-4">
                    <label class="filter-label">Store Type</label>
                    <select id="filter-store-type" class="form-select select2">
                        <option value="">All Types</option>
                        @foreach($storeTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="filter-label">Model Type</label>
                    <select id="filter-mt" class="form-select select2">
                        <option value="">All Models</option>
                        @foreach($modelTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="filter-label">Pricing Tier</label>
                    <select id="filter-pricing-tier" class="form-select select2">
                        <option value="">All Tiers</option>
                        @foreach($pricingTiers as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="filter-label">State</label>
                    <select id="filter_state" class="form-select select2">
                        @if(isset($stateFilter) && $stateFilter)
                            <option value="{{ $stateFilter->city_state }}" selected>{{ $stateFilter->city_state }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="filter-label">City</label>
                    <select id="filter_city" class="form-select select2">
                         @if(isset($cityFilter) && $cityFilter)
                            <option value="{{ $cityFilter->city_id }}" selected>{{ $cityFilter->city_name }}</option>
                        @endif
                    </select>
                </div>
                 <div class="col-md-10"></div>
                 <div class="col-md-2 text-end">
                    <button id="reset-filters" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-undo me-1"></i> Reset Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="tab-content" id="myTabContent">
            <div class="table-responsive">
                <table class="table table-striped fdf" id="stores-table" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Code</th>
                            <th scope="col">Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Model Type</th>
                            <th scope="col">Address</th>
                            <th scope="col">State</th>
                            <th scope="col">City</th>
                            <th scope="col">Email</th>
                            <th scope="col">Mobile</th>
                            <th scope="col">Pricing Tier</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="browser-file" tabindex="-1" aria-labelledby="browser-file" aria-hidden="true">
        <form id="fileUploader" method="POST" action="{{ route('import-stores') }}" enctype="multipart/form-data"> @csrf
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Browse File</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="xlsxfile" class="form-label">Select a File</label>
                            <input type="file" name="xlsx" class="form-control" id="xlsx">
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Import</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/other/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/other/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/js/other/dataTables.bootstrap5.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
    <script>
        $(document).ready(function() {

            jQuery.validator.addMethod("extension", function (value, element, param) {
                if (element.files.length > 0) {
                    const file = element.files[0];
                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    return fileExtension === param.toLowerCase();
                }
                return true;
            }, "Please upload a valid file type.");

            $('#fileUploader').validate({
                rules: {
                    xlsx: {
                        required: true,
                        extension: 'xlsx'
                    }
                },
                messages: {
                    xlsx: {
                        required: "Please select a file",
                        extension: 'Only .xlsx file is allowed for import'
                    }
                },
                submitHandler: function(form, event) {
                    event.preventDefault();

                    let formData = new FormData(form);

                    $.ajax({
                        url: "{{ route('import-stores') }}",
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        beforeSend: function () {
                            $('body').find('.LoaderSec').removeClass('d-none');
                        },
                        success: function(response) {
                            $('body').find('.LoaderSec').addClass('d-none');
                            if (response.status) {
                                $('#browser-file').modal('hide');
                                $('form#fileUploader')[0].reset();
                                $('.modal-backdrop').remove();

                                Swal.fire('Success', response.message, 'success');
                                location.reload();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }
                    });
                }
            });

            function getQueryParams() {
                const params = {};
                const searchParams = new URLSearchParams(window.location.search);
                for (const [key, value] of searchParams.entries()) {
                    params[key] = value;
                }
                return params;
            }

            $('#export-stores').on('click', function () {
                $.ajax({
                    url: "{{ route('export-stores') }}",
                    type: 'GET',
                    cache: false,
                    xhrFields:{
                        responseType: 'blob'
                    },
                    data: getQueryParams(),
                    beforeSend: function () {
                        $('body').find('.LoaderSec').removeClass('d-none');
                    },
                    success: function(response) {
                        var url = window.URL || window.webkitURL;
                        var objectUrl = url.createObjectURL(response);
                        var a = $("<a />", {
                            href: objectUrl,
                            download: "stores.xlsx"
                        }).appendTo("body")
                        a[0].click()
                        a.remove()
                    },
                    complete: function () {
                        $('body').find('.LoaderSec').addClass('d-none');
                    }
                });
            });            

            $('#filter_state').select2({
                placeholder: 'Select State',
                allowClear: true,
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('state-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,
                            _token: "{{ csrf_token() }}",
                            getall: true
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;

                        return {
                            results: $.map(data.items, function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                };
                            }),
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }

                    var $result = $('<span></span>');
                    $result.text(data.text);
                    return $result;
                }
            }).on('change', function() {
                $('#filter_city').val(null).trigger('change');
            });

            $('#filter_city').select2({
                placeholder: 'Select City',
                allowClear: true,
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('city-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,
                            _token: "{{ csrf_token() }}",
                            state: function() {
                                return $('#filter_state').val();
                            },
                            getall: true
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;

                        return {
                            results: $.map(data.items, function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                };
                            }),
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }

                    var $result = $('<span></span>');
                    $result.text(data.text);
                    return $result;
                }
            });

            $('#filter_dom').select2({
                placeholder: 'Select Manager',
                allowClear: true,
                width: '100%',
                theme: 'classic',
                ajax: {
                    url: "{{ route('users-list') }}",
                    type: "POST",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            searchQuery: params.term,
                            page: params.page || 1,
                            _token: "{{ csrf_token() }}",
                            ignoreDesignation: 1,
                            roles: "{{ implode(',', [Helper::$roles['store-manager'], Helper::$roles['store-employee']]) }}",
                            getall: true
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;

                        return {
                            results: $.map(data.items, function(item) {
                                return {
                                    id: item.id,
                                    text: item.text
                                };
                            }),
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }

                    var $result = $('<span></span>');
                    $result.text(data.text);
                    return $result;
                }
            });

            (function() {
                'use strict';
                window.addEventListener('load', function() {
                    var forms = document.getElementsByClassName('gift-submit-form');
                    var validation = Array.prototype.filter.call(forms, function(form) {
                        form.addEventListener('submit', function(event) {
                            if (form.checkValidity() === false) {
                                event.preventDefault();
                                event.stopPropagation();
                                $(form).trigger('mdFormValidationErrors')
                            } else {
                                $(form).trigger('mdFormValidationSuccess')
                            }
                            form.classList.add('was-validated');
                        }, false);
                    });
                }, false);
            })();

            $('#filter-store-type, #filter-mt, #filter-pricing-tier').select2({
                width: '100%',
                theme: 'classic'
            });

            let usersTable = new DataTable('#stores-table', {
                processing: true,
                serverSide: true,
                ordering: false,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                ajax: {
                    url: "{{ route('stores.index') }}",
                    data: function(d) {
                        const params = {};
                        const searchParams = new URLSearchParams(window.location.search);
                        for (const [key, value] of searchParams.entries()) {
                            params[key] = value;
                        }
                        
                        d.filter_general_search = $('#filter-general-search').val();
                        d.filter_store_type = $('#filter-store-type').val();
                        d.filter_mt = $('#filter-mt').val();
                        d.filter_pricing_tier = $('#filter-pricing-tier').val();
                        d.filter_state = $('#filter_state').val();
                        d.filter_city = $('#filter_city').val();

                        return $.extend({}, d, params);
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'store_type_name',
                        name: 'store_type_name',
                        orderable: false
                    },
                    {
                        data: 'model_type_name',
                        name: 'model_type_name',
                        orderable: false
                    },
                    {
                        data: 'address_1',
                        name: 'address_1',
                        orderable: false
                    },
                    {
                        data: 'state',
                        name: 'state',
                        orderable: false
                    },
                    {
                        data: 'city',
                        name: 'city',
                        orderable: false
                    },
                    {
                        data: 'email',
                        name: 'email',
                        orderable: false
                    },
                    {
                        data: 'mobile',
                        name: 'mobile',
                        orderable: false
                    },
                    // {
                    //     data: 'whatsapp',
                    //     name: 'whatsapp',
                    //     orderable: false
                    // },
                    {
                        data: 'pricing_tier_name',
                        name: 'pricing_tier_name',
                        orderable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    }
                ],
                initComplete: function(settings) {

                }
            });

            // Trigger Reload on Filter Change
            let searchTimeout;
            $('#filter-general-search').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                     usersTable.ajax.reload();
                }, 500);
            });

            $('#filter-store-type, #filter-mt, #filter-pricing-tier, #filter_state, #filter_city').on('change', function() {
                usersTable.ajax.reload();
            });

            // Reset Filters
            $('#reset-filters').on('click', function() {
                $('#filter-general-search').val('');
                $('#filter-store-type, #filter-mt, #filter-pricing-tier, #filter_state, #filter_city').val(null).trigger('change');
                usersTable.ajax.reload();
            });
        });
    </script>
@endpush
