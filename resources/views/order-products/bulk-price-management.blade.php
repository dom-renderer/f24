@extends('layouts.app-master')

@push('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/twitter-bootstrap.min.css') }}"/>
<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}" />
<style>
    .category-card {
        border: 1px solid #e3e6f0;
        border-radius: 6px;
        margin-bottom: 12px;
        background: #fff;
    }
    .category-header {
        padding: 10px 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8f9fc;
    }
    .category-header h5 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
    }
    .product-block {
        border-top: 1px solid #f1f1f1;
        padding: 12px 16px;
    }
    .product-title {
        font-weight: 600;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .units-table th,
    .units-table td {
        vertical-align: middle;
        font-size: 13px;
        white-space: nowrap;
    }
    .units-table thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 5;
    }
    .price-input {
        max-width: 110px;
    }
    .scroll-container {
        max-height: 600px;
        overflow: auto;
        border: 1px solid #e3e6f0;
        border-radius: 6px;
        background: #fff;
    }
    .tier-header {
        font-size: 13px;
        font-weight: 600;
        text-align: center;
    }
    .muted {
        color: #6c757d;
        font-size: 12px;
    }
</style>
@endpush

@section('content')
<div class="bg-light p-4 rounded">
    <h1>{{ $page_title }}</h1>
    <div class="lead">
        {{ $page_description }}
    </div>

    <div class="mt-3 mb-2">
        @include('layouts.partials.messages')
    </div>

    <form method="POST" action="{{ route('bulk-price-management.store') }}">
        @csrf

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="muted">
                Update prices tier-wise for all product units. Leave a field blank to remove that tier price.
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('order-products.index') }}" class="btn btn-outline-secondary btn-sm">
                    Back to Products
                </a>
                <button type="button" id="btnExportBulkPrice" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export (Excel)
                </button>
                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                    <i class="bi bi-upload me-1"></i> Import (Excel)
                </button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-save me-1"></i> Save All Changes
                </button>
            </div>
        </div>

        <div class="scroll-container">
            @forelse($categories as $category)
                @include('order-products.partials.bulk-price-category', [
                    'category' => $category,
                    'pricingTiers' => $pricingTiers,
                    'currencySymbol' => $currencySymbol,
                    'iteration' => $loop->index
                ])
            @empty
                <div class="p-3 text-center text-muted">
                    No categories or products found. Please create products first.
                </div>
            @endforelse
        </div>

        <div class="mt-3 text-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> Save All Changes
            </button>
        </div>
    </form>

    {{-- Import Modal --}}
    <div class="modal fade" id="bulkImportModal" tabindex="-1" aria-labelledby="bulkImportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkImportModalLabel">
                        <i class="bi bi-upload me-1"></i> Import Bulk Prices (Excel)
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bulkImportForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-warning small">
                            Please use the exported file format. Do not edit the first <strong>ID (DO NOT EDIT)</strong> column or add new rows/products.
                        </div>
                        <div class="mb-3">
                            <label for="bulkImportFile" class="form-label fw-bold">Select Excel file (.xlsx)</label>
                            <input class="form-control" type="file" id="bulkImportFile" name="file" accept=".xlsx" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="btnBulkImportSubmit">
                            <i class="bi bi-upload me-1"></i> Upload &amp; Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-category-toggle').forEach(function (header) {
            header.addEventListener('click', function () {
                var body = this.nextElementSibling;
                if (!body) return;
                body.classList.toggle('d-none');
                var icon = this.querySelector('.toggle-icon');
                if (icon) {
                    icon.classList.toggle('bi-chevron-down');
                    icon.classList.toggle('bi-chevron-up');
                }
            });
        });

        const exportBtn = document.getElementById('btnExportBulkPrice');
        if (exportBtn) {
            exportBtn.addEventListener('click', function () {
                window.location.href = "{{ route('bulk-price-management.export') }}";
            });
        }

        const importForm = document.getElementById('bulkImportForm');
        const importFileInput = document.getElementById('bulkImportFile');
        const importBtn = document.getElementById('btnBulkImportSubmit');

        if (importForm) {
            importForm.addEventListener('submit', function (e) {
                e.preventDefault();

                if (!importFileInput.files.length) {
                    Swal.fire('Error', 'Please select an Excel (.xlsx) file.', 'error');
                    return;
                }

                if (importFileInput.files.length > 1) {
                    Swal.fire('Error', 'Please select only one file.', 'error');
                    return;
                }

                const file = importFileInput.files[0];
                const ext = file.name.split('.').pop().toLowerCase();
                if (ext !== 'xlsx') {
                    Swal.fire('Invalid File', 'Only .xlsx files are allowed.', 'error');
                    return;
                }

                const formData = new FormData();
                formData.append('file', file);

                importBtn.disabled = true;
                importBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Importing...';

                fetch("{{ route('bulk-price-management.import') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                })
                    .then(async (response) => {
                        const data = await response.json();
                        importBtn.disabled = false;
                        importBtn.innerHTML = '<i class="bi bi-upload me-1"></i> Upload & Import';

                        if (!response.ok || !data.status) {
                            Swal.fire('Import Failed', data.message || 'Something went wrong during import.', 'error');
                            return;
                        }

                        let statsHtml = '';
                        if (data.stats) {
                            statsHtml = '<ul class="text-start" style="margin-left:20px;">' +
                                '<li><strong>Rows processed:</strong> ' + (data.stats.rows_processed ?? 0) + '</li>' +
                                '<li><strong>Prices created:</strong> ' + (data.stats.prices_created ?? 0) + '</li>' +
                                '<li><strong>Prices updated:</strong> ' + (data.stats.prices_updated ?? 0) + '</li>' +
                                '<li><strong>Prices deleted:</strong> ' + (data.stats.prices_deleted ?? 0) + '</li>' +
                                '<li><strong>Rows with unknown products/units:</strong> ' + (data.stats.rows_skipped_new_items ?? 0) + '</li>' +
                                '<li><strong>Rows with invalid data:</strong> ' + (data.stats.rows_invalid ?? 0) + '</li>' +
                                '</ul>';
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Import Completed',
                            html: (data.message || 'Bulk prices imported successfully.') + statsHtml
                        }).then(() => {
                            window.location.reload();
                        });
                    })
                    .catch(() => {
                        importBtn.disabled = false;
                        importBtn.innerHTML = '<i class="bi bi-upload me-1"></i> Upload & Import';
                        Swal.fire('Error', 'Something went wrong while importing the file.', 'error');
                    });
            });
        }
    });
</script>
@endpush

