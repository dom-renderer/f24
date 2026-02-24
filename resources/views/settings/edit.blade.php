@extends('layouts.app-master')

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/css/jquery.datetimepicker.css') }}">
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/summernote.min.css') }}">
@endpush

@section('content')

<div class="container-fluid py-4">
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h4 class="mb-1">Settings</h4>
            <p class="text-muted small mb-0">Manage system settings and configurations</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif    

    @include('layouts.partials.messages')

    @if(auth()->check() && auth()->user()->id == 1)
    <div class="row">
        <div class="col-lg-12">
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>General & Order Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold" for="company_common_upi">Company Common UPI <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control readonly" name="company_common_upi" id="company_common_upi" value="{{ old('company_common_upi', $setting->company_common_upi ?? '') }}" required readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" id="upi-lock" type="button"> <i class="bi bi-unlock-fill"></i> </button>
                                    </div>
                                </div>
                                <small class="text-muted">UPI ID for company payments.</small>
                                @if(file_exists(public_path('storage/qr-codes/company_upi_qr.png')))
                                    <br>
                                    <a href="{{ asset('storage/qr-codes/company_upi_qr.png') }}" target="_blank" class="text-primary small">View QR Code</a>
                                @endif
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold" for="default_currency_id">Default Currency <span class="text-danger">*</span></label>
                                <select name="default_currency_id" id="default_currency_id" class="form-control select2" required>
                                    <option value="">Select Default Currency</option>
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->id }}" {{ old('default_currency_id', $setting->default_currency_id ?? '') == $currency->id ? 'selected' : '' }}>{{ $currency->name }} ({{ $currency->symbol }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Default currency for the system.</small>
                                <a href="{{ route('currencies.index') }}" class="text-primary small"> Manage Currencies </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold" for="company_store_discount">Company Store Discount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" name="company_store_discount" id="company_store_discount" value="{{ old('company_store_discount', $setting->company_store_discount ?? '') }}" required>
                                <small class="text-muted">Company store discount percentage.</small>
                            </div>                            
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold" for="company_name">Company Name</label>
                                <input type="text" class="form-control" name="company_name" id="company_name" value="{{ old('company_name', $setting->company_name ?? '') }}">
                                <small class="text-muted">Company Name.</small>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold" for="address">Address</label>
                                <textarea name="address" id="address" class="form-control summernote-editor">{{ old('address', $setting->address ?? '') }}</textarea>
                                <small class="text-muted">Company Address.</small>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold" for="gstin">GSTIN</label>
                                <input type="text" class="form-control" name="gstin" id="gstin" value="{{ old('gstin', $setting->gstin ?? '') }}">
                                <small class="text-muted">Company GSTIN.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold" for="cin">CIN</label>
                                <input type="text" class="form-control" name="cin" id="cin" value="{{ old('cin', $setting->cin ?? '') }}">
                                <small class="text-muted">Company CIN.</small>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer bg-white border-top text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection

@push('js')
    <script src="{{ asset('assets/js/jquery.datetimepicker.js') }}"></script>
    <script src="{{ asset('assets/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/js/summernote.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            $('.select2').select2({
                width: '100%',
                theme: 'classic'
            });

            $('.summernote-editor').summernote({
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['fontname', ['fontname']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });

            $('#upi-lock').on('click', function () {
                if ($('#company_common_upi').hasClass('readonly')) {
                    $('#company_common_upi').removeClass('readonly')
                    $('#company_common_upi').attr('readonly', false)
                } else {
                    $('#company_common_upi').addClass('readonly')
                    $('#company_common_upi').attr('readonly', true)
                }
                $(this).find('i').toggleClass('bi-lock-fill bi-unlock-fill');
            });
        });
    </script>
@endpush
