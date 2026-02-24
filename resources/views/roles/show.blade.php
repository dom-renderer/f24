
@extends('layouts.app-master')

@section('content')
<div class="bg-light p-4 rounded">
    <div class="mt-4">

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input readonly value="{{ $role->name }}"
                       type="text"
                       class="form-control"
                       name="name"
                       required
                       @if(in_array($role->id, [1,2,3])) readonly @endif>
            </div>

            <label class="form-label mb-3">Assign Permissions</label>

            @php
                $permissionGroups = [];

                foreach ($permissions as $permission) {
                    $group = explode('.', $permission->name)[0];
                    $permissionGroups[$group][] = $permission;
                }
            @endphp

            <div class="row g-3">
                @foreach($permissionGroups as $group => $groupPermissions)
                    @php
                        $groupChecked = collect($groupPermissions)
                            ->pluck('name')
                            ->every(fn ($name) => in_array($name, $rolePermissions));
                    @endphp

                    <div class="col-md-4 d-flex">
                        <div class="card w-100 h-100">

                            <div class="card-header d-flex justify-content-between align-items-center">
                                <strong>{{ Str::title(str_replace('-', ' ', $group)) }}</strong>
                            </div>

                            <div class="card-body">
                                @foreach($groupPermissions as $permission)
                                    <div class="form-check mb-1">
                                        <input readonly type="checkbox"
                                               name="permission[{{ $permission->name }}]"
                                               value="{{ $permission->name }}"
                                               class="form-check-input permission permission-{{ $group }}"
                                               id="permission-{{ $permission->id }}"
                                               {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                               for="permission-{{ $permission->id }}">
                                            {{ $permission->title }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary">Back</a>
            </div>

    </div>
</div>
@endsection


@push('js')
<script>
$(document).ready(function () {

    $('.group-check').on('change', function () {
        let group = $(this).data('group');
        $('.permission-' + group).prop('checked', $(this).is(':checked'));
    });

    $('.permission').on('change', function () {
        let classes = $(this).attr('class').split(' ');
        let groupClass = classes.find(c => c.startsWith('permission-') && c !== 'permission');
        let group = groupClass.replace('permission-', '');

        let total = $('.permission-' + group).length;
        let checked = $('.permission-' + group + ':checked').length;

        $('#group-' + group).prop('checked', total === checked);
    });

});
</script>
@endpush
