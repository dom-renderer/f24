@extends('layouts.app-master')


@section('content')
    <div class="row">
        <div class="col-12 mb-3 d-flex justify-content-between align-items-center">
            <h2>Store Details</h2>
            <div>
                <a href="{{ route('stores.index') }}" class="btn btn-outline-secondary me-2">Back to Stores</a>
                @can('stores.edit')
                    <a href="{{ route('stores.edit', $store->id) }}" class="btn btn-warning">Edit</a>
                @endcan
            </div>
        </div>

        <div class="col-md-8">
            <div class="card store-detail-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ $store->name }}</h5>
                        <small class="text-muted">Code: {{ $store->code }}</small>
                    </div>
                    <div>
                        @if(isset($store->modeltype->id))
                            <span class="badge bg-primary text-white me-1">
                                {{ $store->modeltype->name }}
                            </span>
                        @endif
                        @if(isset($store->storetype->id))
                            <span class="badge bg-danger text-white">
                                {{ $store->storetype->name }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Address:</div>
                        <div class="col-md-9">
                            {{ $store->address1 }}<br>
                            {{ $store->address2 }} {{ $store->block }} {{ $store->street }}
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Landmark:</div>
                        <div class="col-md-9">{{ $store->landmark }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">State:</div>
                        <div class="col-md-9">{{ $store->thecity->city_state ?? '' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">City:</div>
                        <div class="col-md-9">{{ $store->thecity->city_name ?? '' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Pricing Tier:</div>
                        <div class="col-md-9">{{ $store->pricingTier->name ?? 'N/A' }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Location:</div>
                        <div class="col-md-9">
                            @if ($store->location && $store->location != 'location')
                                <a title="{{ $store->location }}" target="_blank"
                                   href="{{ $store->location_url ?? 'javascript:void(0);' }}">
                                    {{ \Illuminate\Support\Str::limit($store->location, 40) }}
                                </a>
                            @else
                                {{ $store->location }}
                            @endif
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Latitude:</div>
                        <div class="col-md-9">{{ $store->map_latitude }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Longitude:</div>
                        <div class="col-md-9">{{ $store->map_longitude }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Opening Time:</div>
                        <div class="col-md-9">{{ $store->open_time }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Closing Time:</div>
                        <div class="col-md-9">{{ $store->close_time }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Ops Start:</div>
                        <div class="col-md-9">{{ $store->ops_start_time }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-3 fw-bold">Ops End:</div>
                        <div class="col-md-9">{{ $store->ops_end_time }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Contact</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Email:</div>
                        <div class="col-md-8">{{ $store->email }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Mobile:</div>
                        <div class="col-md-8">{{ $store->mobile }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">Whatsapp:</div>
                        <div class="col-md-8">{{ $store->whatsapp }}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4 fw-bold">UPI Handle:</div>
                        <div class="col-md-8">{{ $store->upi_handle }}</div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Employees</h5>
                </div>
                <div class="card-body">
                    @if($store->users && $store->users->count())
                        <ul class="list-group list-group-flush">
                            @foreach($store->users as $u)
                                <li class="list-group-item ps-0 pe-0">
                                    {{ $u->employee_id }} - {{ $u->name }} {{ $u->middle_name }} {{ $u->last_name }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mb-0 text-muted">No employees assigned.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

