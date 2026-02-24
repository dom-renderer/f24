@extends('layouts.app-master')

@push('css')
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />

    <style type="text/css">
        #map {
            height: 650px;
            width: 100%;
        }

        .pac-container {
            background-color: #FFF;
            z-index: 2000;
            position: fixed;
            display: inline-block;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="col-title mb-30 d-flex justify-content-between align-items-center">
                <h2>Add Store</h2>
                <a href="{{ route('stores.index') }}" class="btn btn-outline-secondary">Back to Stores</a>
            </div>

            <div class="fursa-form">
                <form method="POST" action="{{ route('stores.store') }}" class="gift-submit-form"> @csrf
                    <input name="location" id="location" type="hidden" value="{{ old('location') }}">

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <div class="">
                                <label class="form-label" for="store_type">Type</label>
                                <select name="store_type" id="store_type" class="form-control" required>
                                    <option value=""></option>
                                    @foreach ($storeTypes as $typeRow)
                                        <option value="{{ $typeRow->id }}"
                                            @if (old('store_type') == $typeRow->id) selected @endif>
                                            {{ str_replace('-', ' ', strtoupper($typeRow->name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($errors->has('store_type'))
                                    <span class="text-danger text-left">{{ $errors->first('store_type') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2" id="model_type_container">
                            <div class="">
                                <label class="form-label" for="model_type">Model Type</label>
                                <select name="model_type" id="model_type" class="form-control">
                                    <option value=""></option>
                                    @foreach ($modelTypes as $typeRow)
                                        <option value="{{ $typeRow->id }}"
                                            @if (old('model_type') == $typeRow->id) selected @endif>
                                            {{ $typeRow->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($errors->has('model_type'))
                                    <span class="text-danger text-left">{{ $errors->first('model_type') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2" id="pricing_tier_container">
                            <div class="">
                                <label class="form-label" for="pricing_tier_id">Pricing Tier</label>
                                <select name="pricing_tier_id" id="pricing_tier_id" class="form-control">
                                    <option value="">Select Tier</option>
                                    @foreach ($pricingTiers as $tier)
                                        <option value="{{ $tier->id }}"
                                            @if (old('pricing_tier_id') == $tier->id) selected @endif>
                                            {{ $tier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($errors->has('pricing_tier_id'))
                                    <span class="text-danger text-left">{{ $errors->first('pricing_tier_id') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="">
                                <label class="form-label" for="locname">Name</label>
                                <input name="name" type="text" class="form-control" id="locname"
                                    placeholder="Store Name" value="{{ old('name') }}" required>
                                @if ($errors->has('name'))
                                    <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="">
                                <label class="form-label" for="loccode">Code</label>
                                <input name="code" type="text" class="form-control" id="loccode"
                                    placeholder="Store Code" value="{{ old('code') }}" required>
                                @if ($errors->has('code'))
                                    <span class="text-danger text-left">{{ $errors->first('code') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="searchTextField">Address Line 1</label>
                                <input name="address1" type="text" class="form-control" id="searchTextField"
                                    placeholder="Address Line 1" value="{{ old('address1') }}">
                                @if ($errors->has('address1'))
                                    <span class="text-danger text-left">{{ $errors->first('address1') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="addrln2">Address Line 2</label>
                                <input name="address2" type="text" class="form-control" id="addrln2"
                                    placeholder="Address Line 2" value="{{ old('address2') }}">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="">
                                <label class="form-label" for="block">Block</label>
                                <input name="block" type="text" class="form-control" placeholder="Block"
                                    id="block" value="{{ old('block') }}">
                                @if ($errors->has('block'))
                                    <span class="text-danger text-left">{{ $errors->first('block') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="">
                                <label class="form-label" for="street">Street</label>
                                <input name="street" type="text" class="form-control" placeholder="Street"
                                    value="{{ old('street') }}">
                                @if ($errors->has('street'))
                                    <span class="text-danger text-left">{{ $errors->first('street') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="">
                                <label class="form-label" for="landmark">Landmark</label>
                                <input name="landmark" type="text" id="landmark" class="form-control"
                                    placeholder="Landmark" value="{{ old('landmark') }}">
                                @if ($errors->has('landmark'))
                                    <span class="text-danger text-left">{{ $errors->first('landmark') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="state">State</label>
                                <select name="state" id="state" required></select>
                                @if ($errors->has('state'))
                                    <span class="text-danger text-left">{{ $errors->first('state') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="city">City</label>
                                <select name="city" id="city" required></select>
                                @if ($errors->has('city'))
                                    <span class="text-danger text-left">{{ $errors->first('city') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="employees">Employees</label>
                        <select name="employees[]" id="employees" class="form-control" multiple required></select>
                        @if ($errors->has('employees'))
                            <span class="text-danger text-left">{{ $errors->first('employees') }}</span>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="">
                                <label class="form-label" for="location_url">Location URL</label>
                                <div class="input-group mb-3">
                                    <input name="location_url" type="text" class="form-control" id="location_url"
                                        placeholder="Location URL" onkeydown="return false;"
                                        style="caret-color: transparent !important;" value="{{ old('location_url') }}" />
                                    <span class="input-group-text cursor-pointer" id="map-location"
                                        data-bs-toggle="modal" data-bs-target="#locationURLMap">
                                        <i class="bi bi-pin-map"></i>
                                    </span>
                                </div>
                                @if ($errors->has('location_url'))
                                    <span class="text-danger text-left">{{ $errors->first('location_url') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="map_latitude">Latitude</label>
                                <input name="map_latitude" type="text" class="form-control" id="map_latitude"
                                    placeholder="Map Latitude" style="caret-color: transparent !important;"
                                    value="{{ old('map_latitude') }}" />
                                @if ($errors->has('map_latitude'))
                                    <span class="text-danger text-left">{{ $errors->first('map_latitude') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="map_longitude">Longitude</label>
                                <input name="map_longitude" type="text" class="form-control" id="map_longitude"
                                    placeholder="Map Longitude" style="caret-color: transparent !important;"
                                    value="{{ old('map_longitude') }}" />
                                @if ($errors->has('map_longitude'))
                                    <span class="text-danger text-left">{{ $errors->first('map_longitude') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="email">Email</label>
                                <input name="email" type="email" class="form-control" placeholder="Email"
                                    id="email" value="{{ old('email') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="upi_handle">UPI Handle</label>
                                <div class="input-group mb-3">
                                    <input name="upi_handle" type="text" class="form-control readonly"
                                        placeholder="UPI Handle" id="upi_handle" value="{{ old('upi_handle') }}"
                                        readonly>
                                    <span class="input-group-text" id="upi-lock">
                                        <i class="bi bi-unlock-fill"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="mobile">Mobile Number</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text" id="prefix-mobile">+91</span>
                                    <input name="mobile" type="hidden" class="form-control" id="mobile"
                                        placeholder="Mobile">
                                    <input name="mobile_type" type="text" class="form-control" id="mobile_type"
                                        placeholder="Mobile" value="{{ old('mobile_type') }}">
                                    @if ($errors->has('mobile_type'))
                                        <span class="text-danger text-left">{{ $errors->first('mobile_type') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="">
                                <label class="form-label" for="whatsapp">Whatsapp Number</label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text" id="prefix-whatsapp">+91</span>
                                    <input name="whatsapp" type="hidden" class="form-control" id="whatsapp"
                                        placeholder="WhatsApp">
                                    <input name="whatsapp_type" type="text" class="form-control" id="whatsapp_type"
                                        placeholder="WhatsApp" value="{{ old('whatsapp_type') }}">
                                    @if ($errors->has('whatsapp_type'))
                                        <span class="text-danger text-left">{{ $errors->first('whatsapp_type') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="">
                                <label class="form-label" for="optime">Opening Time</label>
                                <input name="open_time" type="text" class="form-control timepicker" id="optime"
                                    value="{{ old('open_time') }}" placeholder="Opening Time" required>
                                @if ($errors->has('open_time'))
                                    <span class="text-danger text-left">{{ $errors->first('open_time') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="">
                                <label class="form-label" for="cltime">Closing Time</label>
                                <input name="close_time" type="text" class="form-control timepicker" id="cltime"
                                    placeholder="Closing Time" value="{{ old('close_time') }}" required>
                                @if ($errors->has('close_time'))
                                    <span class="text-danger text-left">{{ $errors->first('close_time') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="">
                                <label class="form-label" for="ops_start_time">Operation Start Time</label>
                                <input name="ops_start_time" type="text" class="form-control timepicker"
                                    id="ops_start_time" value="{{ old('ops_start_time') }}"
                                    placeholder="Operation Start Time" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="">
                                <label class="form-label" for="ops_end_time">Operation End Time</label>
                                <input name="ops_end_time" type="text" class="form-control timepicker"
                                    id="ops_end_time" placeholder="Operation End Time" value="{{ old('ops_end_time') }}"
                                    required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Save
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="locationURLMap" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="locationURLMapLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:1700px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">Map</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row" id="location_url_map">
                        <div class="col-12">
                            <input id="pac-input" class="controls" type="text" placeholder="Search Box" />
                            <div id="map"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveLocation">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"
        integrity="sha512-pHVGpX7F/27yZ0ISY+VVjyULApbDlD0/X0rgGbTqCE7WFW5MezNTWG/dnhtbBuICzsd0WQPgpE4REBLv+UqChw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAP_KEY') }}&libraries=places" async defer>
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('gift-submit-form');
                Array.prototype.filter.call(forms, function(form) {
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

        jQuery(document).ready(function($) {
            $('#upi-lock').on('click', function() {
                if ($('#upi_handle').hasClass('readonly')) {
                    $('#upi_handle').removeClass('readonly')
                    $('#upi_handle').attr('readonly', false)
                } else {
                    $('#upi_handle').addClass('readonly')
                    $('#upi_handle').attr('readonly', true)
                }
                $(this).find('i').toggleClass('bi-lock-fill bi-unlock-fill');
            });

            $('#state').select2({
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
                            _token: "{{ csrf_token() }}"
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
                }
            }).on('change', function() {
                $('#city').val(null).trigger('change');
            });

            $('#city').select2({
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
                                return $('#state option:selected').val();
                            }
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
                }
            });

            var currentRoles =
                "{{ implode(',', [Helper::$roles['store-manager'], Helper::$roles['store-employee']]) }}";
            var roleMapping = {
                1: "{{ implode(',', [Helper::$roles['store-manager'], Helper::$roles['store-employee']]) }}",
                2: "{{ Helper::$roles['factory-manager'] }}",
                3: "{{ Helper::$roles['dealer'] }}"
            };

            $('#employees').select2({
                placeholder: 'Select Employees',
                allowClear: true,
                width: '100%',
                theme: 'classic',
                multiple: true,
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
                            roles: currentRoles,
                            for_store_listing: 1,
                            ignoreDesignation: 1
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
                }
            });

            $('#store_type').on('change', function() {
                var typeId = $(this).val();
                var previousRoles = currentRoles;

                if (typeId == 1) { // Store
                    $('#model_type_container').show();
                    $('#model_type').prop('required', true);
                    currentRoles = roleMapping[1];
                } else if (typeId == 2) { // Factory
                    $('#model_type_container').hide();
                    $('#model_type').prop('required', false);
                    currentRoles = roleMapping[2];
                } else if (typeId == 3) { // Dealer Location
                    $('#model_type_container').hide();
                    $('#model_type').prop('required', false);
                    currentRoles = roleMapping[3];
                } else {
                    $('#model_type_container').hide();
                    $('#model_type').prop('required', false);
                }

                if (previousRoles !== currentRoles) {
                    $('#employees').val(null).trigger('change');
                }
            });

            if ($('#store_type').val()) {
                $('#store_type').trigger('change');
            } else {
                $('#model_type_container').hide();
                $('#model_type').prop('required', false);
            }

            $('#store_type').select2({
                placeholder: 'Select Location Type',
                allowClear: true,
                width: '100%',
                theme: 'classic'
            });

            $('#model_type').select2({
                placeholder: 'Select Model Type',
                allowClear: true,
                width: '100%',
                theme: 'classic'
            });

            $('#pricing_tier_id').select2({
                placeholder: 'Select Pricing Tier',
                allowClear: true,
                width: '100%',
                theme: 'classic'
            });

            $('.timepicker').timepicker({
                timeFormat: 'h:mm p',
                interval: 15,
                dynamic: false,
                dropdown: true,
                scrollbar: true
            });

            $('#mobile_type,#whatsapp').mask('0#');
            $('#mobile_type').on('input', function(e) {
                $("#mobile").val("91" + e.target.value);
                if (e.target.value.length > 0 && e.target.value.length < 8) {
                    this.setCustomValidity('Please enter a valid mobile number!');
                } else {
                    this.setCustomValidity('');
                }
            });
            $('#whatsapp_type').on('input', function(e) {
                $("#whatsapp").val("91" + e.target.value);
                if (e.target.value.length > 0 && e.target.value.length < 8) {
                    this.setCustomValidity('Please enter a valid whatsapp number!');
                } else {
                    this.setCustomValidity('');
                }
            });
        });

        let thisLat = 22.2882281;
        let thisLong = 70.792489;
        let thisLatLongUrl = '';
        let thePlaceName = '';

        function initAutocomplete() {
            const map = new google.maps.Map(document.getElementById("map"), {
                center: {
                    lat: thisLat,
                    lng: thisLong
                },
                zoom: 13,
                mapTypeId: "roadmap",
            });

            const input = document.getElementById("pac-input");
            const searchBox = new google.maps.places.SearchBox(input);

            map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
            map.addListener("bounds_changed", () => {
                searchBox.setBounds(map.getBounds());
            });

            let marker = null;

            function createMarker(position, title) {
                if (marker) {
                    marker.setMap(null);
                }

                const icon = {
                    url: "{{ url('assets/images/markers.png') }}",
                    scaledSize: new google.maps.Size(30, 30),
                };

                marker = new google.maps.Marker({
                    position,
                    map,
                    icon,
                    title,
                });

                return marker;
            }

            function logPlaceDetails(position, name) {
                const lat = position.lat();
                const lng = position.lng();
                const url = `https://www.google.com/maps?q=${lat},${lng}`;

                thisLat = lat;
                thisLong = lng;
                thisLatLongUrl = url;
                thePlaceName = name;
            }

            map.addListener("click", (event) => {
                const position = event.latLng;
                createMarker(position, "");
                logPlaceDetails(position, "");
            });

            searchBox.addListener("places_changed", () => {
                const places = searchBox.getPlaces();
                if (places.length == 0) {
                    return;
                }

                const bounds = new google.maps.LatLngBounds();

                places.forEach((place) => {
                    if (!place.geometry || !place.geometry.location) {
                        return;
                    }

                    const position = place.geometry.location;
                    createMarker(position, place.name);
                    logPlaceDetails(position, place.name);

                    if (place.geometry.viewport) {
                        bounds.union(place.geometry.viewport);
                    } else {
                        bounds.extend(position);
                    }
                });

                map.fitBounds(bounds);
            });
        }

        const locationURLMapModal = document.getElementById('locationURLMap');
        locationURLMapModal.addEventListener('shown.bs.modal', function() {
            initAutocomplete();

            document.getElementById('saveLocation').addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('location').value = thePlaceName;
                document.getElementById('location_url').value = thisLatLongUrl;
                document.getElementById('map_latitude').value = thisLat;
                document.getElementById('map_longitude').value = thisLong;

                $(locationURLMapModal).modal('hide');
            }, {
                once: true
            });
        });
    </script>
@endpush
