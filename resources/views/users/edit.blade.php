@extends('layouts.app-master')

@push('css')
    <link href="{{ asset('assets/css/select2.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/custom-select-style.css') }}">
@endpush

@section('content')
    <form method="post" class="edituser" action="{{ route('users.update', $user->id) }}" enctype="multipart/form-data">
        <div class="bg-light p-4 rounded row">
            <div class="col-12">
                @method('patch')
                @csrf

                <input type="hidden" name="id" value="{{ $user->id }}">

                <div class="mb-3">
                    <label for="name" class="form-label">First Name</label>
                    <input value="{{ old('name', $user->name) }}" type="text" class="form-control" name="name"
                        placeholder="Name" required>

                    @if ($errors->has('name'))
                        <span class="text-danger text-left">{{ $errors->first('name') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="middle_name" class="form-label">Middle Name</label>
                    <input value="{{ old('middle_name', $user->middle_name) }}" type="text" class="form-control"
                        name="middle_name" placeholder="Middle Name">

                    @if ($errors->has('middle_name'))
                        <span class="text-danger text-left">{{ $errors->first('middle_name') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input value="{{ old('last_name', $user->last_name) }}" type="text" class="form-control"
                        name="last_name" placeholder="Last Name">

                    @if ($errors->has('last_name'))
                        <span class="text-danger text-left">{{ $errors->first('last_name') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input value="{{ old('email', $user->email) }}" type="email" class="form-control" name="email"
                        placeholder="Email address" required>
                    @if ($errors->has('email'))
                        <span class="text-danger text-left">{{ $errors->first('email') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="upi_handle" class="form-label">UPI Handle</label>
                    <div class="input-group">
                        <input value="{{ old('upi_handle', $user->upi_handle) }}" type="text" class="form-control readonly" name="upi_handle" id="upi_handle" placeholder="UPI Handle" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" id="upi-lock" type="button"> <i class="bi bi-unlock-fill"></i> </button>
                        </div>
                    </div>
                    @if ($errors->has('upi_handle'))
                        <span class="text-danger text-left">{{ $errors->first('upi_handle') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="employee_id" class="form-label">Employee ID</label>
                    <input value="{{ old('employee_id', $user->employee_id) }}" type="text" class="form-control"
                        name="employee_id" placeholder="EMPLOYEE ID">
                    @if ($errors->has('employee_id'))
                        <span class="text-danger text-left">{{ $errors->first('employee_id') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input value="{{ old('username', $user->username) }}" type="text" class="form-control" name="username"
                        placeholder="Username" required>
                    @if ($errors->has('username'))
                        <span class="text-danger text-left">{{ $errors->first('username') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone number</label>
                    <input value="{{ old('phone_number', $user->phone_number) }}" type="text" class="form-control"
                        name="phone_number" placeholder="Phone number" required>
                    @if ($errors->has('phone_number'))
                        <span class="text-danger text-left">{{ $errors->first('phone_number') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="profile" class="form-label">Profile image</label>
                    <input value="{{ old('profile') }}" type="file" class="form-control" name="profile" />
                    @if ($errors->has('profile'))
                        <span class="text-danger text-left">{{ $errors->first('profile') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">Status</label>
                    <select class="form-control" name="status" id="status">
                        <option value="1" <?php echo $status = $user->status == 1 ? "selected='selected'" : ''; ?>>Enable
                        </option>
                        <option value="0" <?php echo $status = $user->status == 0 ? "selected='selected'" : ''; ?>>Disable
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input value="{{ old('password') }}" type="password" class="form-control" name="password"
                        placeholder="Password">
                    @if ($errors->has('password'))
                        <span class="text-danger text-left">{{ $errors->first('password') }}</span>
                    @endif
                </div>


                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="">Select role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @if($role->id == $user->roles[0]->id) selected @endif>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->has('role'))
                        <span class="text-danger text-left">{{ $errors->first('role') }}</span>
                    @endif
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('users.index') }}" class="btn btn-default">Cancel</a>
                </div>

            </div>
        </div>
    </form>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#upi-lock').on('click', function () {
                if ($('#upi_handle').hasClass('readonly')) {
                    $('#upi_handle').removeClass('readonly')
                    $('#upi_handle').attr('readonly', false)
                } else {
                    $('#upi_handle').addClass('readonly')
                    $('#upi_handle').attr('readonly', true)
                }
                $(this).find('i').toggleClass('bi-lock-fill bi-unlock-fill');
            });

            $('#role').select2({
                placeholder: "Select a role",
                allowClear: true,
                width: "100%",
                theme: 'classic'
            })
        });
    </script>
@endpush