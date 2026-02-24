@extends('layouts.app-master')

@section('content')
    <div class="bg-light p-4 rounded">
        <h1>Edit Currency</h1>
        <div class="container mt-4">

            <form method="POST" action="{{ route('currencies.update', $currency->id) }}" id="currencyForm">
                @method('patch')
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input value="{{ $currency->name }}" 
                        type="text" 
                        class="form-control" 
                        name="name" 
                        placeholder="Currency Name" required>

                    @if ($errors->has('name'))
                        <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="symbol" class="form-label">Symbol</label>
                    <input value="{{ $currency->symbol }}" 
                        type="text" 
                        class="form-control" 
                        name="symbol" 
                        placeholder="Currency Symbol (e.g. $, ₹)" required>
                    @if ($errors->has('symbol'))
                        <span class="text-danger text-left">{{ $errors->first('symbol') }}</span>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary">Update Currency</button>
                <a href="{{ route('currencies.index') }}" class="btn btn-default">Back</a>
            </form>
        </div>

    </div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script>
    $(document).ready(function() {
        $("#currencyForm").validate({
            rules: {
                name: {
                    required: true,
                },
                symbol: {
                    required: true,
                }
            },
            messages: {
                name: {
                    required: "Please enter currency name",
                },
                symbol: {
                    required: "Please enter currency symbol",
                }
            },
            errorElement: 'span',
            errorPlacement: function (error, element) {
                error.addClass('invalid-feedback');
                element.closest('.mb-3').append(error);
            },
            highlight: function (element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
            }
        });
    });
</script>
@endpush
