<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\StoreUserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Import;
use App\Models\User;

class UsersController extends Controller
{
    public function index(Request $request) 
    {
        $page_title = "Users";
        $page_description = "Manage users here";

        $roles = Role::latest()->get();
        $userCount = User::count();
        $archivedUserCount = User::onlyTrashed()->count();

        return view('users.index', compact('page_title','roles', 'userCount', 'archivedUserCount', 'page_description'));
    }

    public function getUsers(Request $request) {

        $roleFilter = $request->roles;
        $users = User::query()->when(!empty($roleFilter), function ($builder) use ($roleFilter) {
            $builder->whereHas('roles', function ($innerBuilder) use ($roleFilter) {
                $innerBuilder->where('name', $roleFilter);
            });
        });

        return datatables()
        ->eloquent($users)
        ->filter(function ($row) {
            if (isset(request('search')['value'])) {
                $row->where(function ($innerRow) {
                    $innerRow->where('name', 'LIKE', '%' . request()->search['value'] . '%')
                    ->orWhere('middle_name', 'LIKE', '%' . request()->search['value'] . '%')
                    ->orWhere('last_name', 'LIKE', '%' . request()->search['value'] . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . request()->search['value'] . '%')
                    ->orWhere('email', 'LIKE', '%' . request()->search['value'] . '%')
                    ->orWhere('username', 'LIKE', '%' . request()->search['value'] . '%');
                });
            }
        })
        ->addColumn('currentrole', function ($row) {
            $name = $row->roles[0]->name ?? '-';

            if ($row->status) {
                return '<span class="badge bg-success"> ' . $name . ' </span>';
            } else {
                return '<span class="badge bg-danger"> ' . $name . ' </span>';
            }
        })
        ->addColumn('action', function ($row) {
            $action = '';

            if (auth()->user()->can('users.show')) {
                $action .= '<a href="'.route("users.show", $row->id).'" class="btn btn-info btn-sm me-2"> Show </a>';
            }

            if (auth()->user()->can('users.edit')) {
                $action .= '<a href="'.route('users.edit', $row->id).'" class="btn btn-warning btn-sm me-2">Edit</a>';
            }

            if (auth()->user()->can('users.destroy')) {
                $action .= '<form method="POST" action="'.route("users.destroy", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-danger btn-sm deleteGroup">Delete</button></form>';
            }

            return $action;
        })
        ->rawColumns(['action', 'currentrole'])
        ->addIndexColumn()
        ->toJson();

    }

    public function getArchiveUsers(Request $request) {

        $roleFilter = $request->roles;
        $users = User::onlyTrashed()->when(!empty($roleFilter), function ($builder) use ($roleFilter) {
            $builder->whereHas('roles', function ($innerBuilder) use ($roleFilter) {
                $innerBuilder->where('name', $roleFilter);
            });
        });

        return datatables()
        ->eloquent($users)
        ->addColumn('currentrole', function ($row) {
            $name = $row->roles[0]->name ?? '-';

            if ($row->status) {
                return '<span class="badge bg-success"> ' . $name . ' </span>';
            } else {
                return '<span class="badge bg-danger"> ' . $name . ' </span>';
            }
        })
        ->addColumn('action', function ($row) {
            $action = '';

            if (auth()->user()->can('users.show')) {
                $action .= '<a href="'.route("users.show.deleted", $row->id).'" class="btn btn-warning btn-sm me-2"> Show </a>';
            }

            if (auth()->user()->can('users.create')) {
                $action .= '<form method="POST" action="'.route("users.restore", $row->id).'" style="display:inline;"><input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="'.csrf_token().'"><button type="submit" class="btn btn-primary btn-sm restoreGroup">Restore</button></form>';
            }

            return $action;
        })
        ->rawColumns(['action', 'currentrole'])
        ->addIndexColumn()
        ->toJson();
    }

    public function create() 
    {
        $page_title = "User Add";
        $roles = Role::all();

        return view('users.create', compact('page_title', 'roles'));
    }

    public function store(StoreUserRequest $request) 
    {
        DB::beginTransaction();

        try {
            $profile = '';

            if ($request->hasFile('profile')) {
                if (!file_exists(storage_path('app/public/users'))) {
                    mkdir(storage_path('app/public/users'), 0777, true);
                }

                $profile = 'USER-' . date('YmdHis') . uniqid() . '.' . $request->file('profile')->getClientOriginalExtension();
                $request->file('profile')->move(storage_path('app/public/users'), $profile);
            }

            $userEloquent = User::create([
                'name' => $request->name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'profile' => $profile,
                'employee_id' => $request->employee_id,
                'username' => $request->username,
                'status' => $request->status,
                'password' => $request->password,
                'upi_handle' => $request->upi_handle
            ]);

            $userEloquent->syncRoles([$request->role]);

            // Generate QR Code
            if (!empty($request->upi_handle)) {
                \App\Helpers\QrCodeHelper::generateQrCode($request->upi_handle, 'user_' . $userEloquent->id . '_qr.png');
            }

            DB::commit();
            return redirect()->route('users.index')->with('success', 'User created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error( 'User creation error ' . $e->getMessage() . ' on line ' . $e->getLine());
            return redirect()->route('users.index')->with('error', 'Something went wrong');
        }
    }

    public function show(User $user) 
    {
        $page_title = 'User show';

        $type = '';
        $store = [];

        return view('users.show', ['user' => $user, 'page_title' => $page_title , 'store' => $store]);
    }

    public function edit(User $user) 
    {        
        $page_title = "User Edit";
        $roles = Role::all();

        return view('users.edit', compact('user', 'roles', 'page_title'));
    }

    public function update(UpdateUserRequest $request) 
    {

        DB::beginTransaction();

        try {
            $userEloquent = User::find($request->id);
        
            $userEloquent->name = $request->name;
            $userEloquent->middle_name = $request->middle_name;
            $userEloquent->last_name = $request->last_name;
            $userEloquent->email = $request->email;
            $userEloquent->username = $request->username;
            $userEloquent->phone_number = $request->phone_number;
            $userEloquent->status = $request->status;
            $userEloquent->employee_id = $request->employee_id;
            $userEloquent->upi_handle = $request->upi_handle;

            $profile = $userEloquent->profile;

            if ($request->hasFile('profile')) {
                if (!file_exists(storage_path('app/public/users'))) {
                    mkdir(storage_path('app/public/users'), 0777, true);
                }
    
                $profile = 'USER-' . date('YmdHis') . uniqid() . '.' . $request->file('profile')->getClientOriginalExtension();
                $request->file('profile')->move(storage_path('app/public/users'), $profile);


                if (file_exists(storage_path("app/public/users/{$profile}")) && file_exists(storage_path("app/public/users/{$userEloquent->profile}"))) {
                    unlink(storage_path("app/public/users/{$userEloquent->profile}"));
                }

                $userEloquent->profile = $profile;
            }
    
            if (!empty($request->password)) {
                $userEloquent->password = $request->password;
            }
    
            $userEloquent->save();
            $userEloquent->syncRoles([$request->role]);          

            // Generate QR Code
            if (!empty($request->upi_handle)) {
                \App\Helpers\QrCodeHelper::generateQrCode($request->upi_handle, 'user_' . $userEloquent->id . '_qr.png');
            }

            DB::commit();
            return redirect()->route('users.index')->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error( 'User updation error ' . $e->getMessage() . ' on line ' . $e->getLine());
            return redirect()->route('users.index')->with('error', 'Something went wrong');
        }
    }

    
    public function showDeleted($id) 
    {
        $user = User::withTrashed()->find($id);

        $store = [];
        $type = '';

        return view('users.show', [
            'user' => $user,
            'store' => $store
        ]);
    }


    public function destroy(User $user) 
    {
        $user->delete();
        
        return redirect()->route('users.index')->with('success', __('User archived successfully.'));
    }

   
    public function restore($id) 
    {
        $user = User::onlyTrashed()->find($id);
        $user->restore();
        
        return redirect()->route('users.index')->with('success', __(' User unable to restore or already restored.'));

    }
    
    public function remove($id) 
    {
        return redirect()->route('users.index')->withSuccess(__('Unable to deleted User.'));
    }

    public static function showChangePasswordForm(){
        return view('auth.changepassword');
    }

    public static function changePassword(Request $request){
        if (!(Hash::check($request->get('current-password'), request()->user()->password))) {
            return redirect()->back()->withErrors("Your current password does not matches with the password you provided. Please try again.");
        }
    
        if(strcmp($request->get('current-password'), $request->get('new-password')) == 0){
            return redirect()->back()->withErrors("New Password cannot be same as your current password. Please choose a different password.");
        }
    
        $request->validate([
            'current-password' => 'required',
            'new-password' => 'required|string|min:8|required_with:new-password_confirmation',
        ]);
    
        //Change Password
        $user = request()->user();
        $user->password = $request->get('new-password');
        $user->password_change_at = \Carbon\Carbon::now();
        $user->save();
    
        return redirect()->route('dashboard.index')->withSuccess("Password changed successfully !");
    
    }

    public static function getAllUsers(Request $request) {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;
        $roles = array_filter(explode(',', $request->roles));
        $branchType = $request->branchType;
        $branchId = $request->branchId;
        $getAll = $request->getall;
        $ignoreDesignation = $request->ignoreDesignation;
        
        $query = User::query();

        if (!empty($queryString)) {
            $query->where(function ($innerB) use ($queryString) {
                $innerB->where('name', 'LIKE', "%{$queryString}%")
                ->orWhere('middle_name', 'LIKE', "%{$queryString}%")
                ->orWhere('last_name', 'LIKE', "%{$queryString}%")
                ->orWhere('phone_number', 'LIKE', "%{$queryString}%")
                ->orWhere('employee_id', 'LIKE', "%{$queryString}%");
            });
        }

        if ($request->filled('for_store_listing')) {
            if ($request->filled('thistore')) {
                $query->where(function ($query) {
                    $query->whereDoesntHave('stores')
                    ->orWhereHas('stores', function ($q) {
                        $q->where('stores.id', request('thistore'));
                    });
                });
            } else {
                $query->whereDoesntHave('stores');
            }
        }

        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($pro) {
            return [
                'id' => $pro->id,
                'text' => (!empty($pro->employee_id) ? "{$pro->employee_id} - " : '') . "{$pro->name} {$pro->middle_name} {$pro->last_name}"
            ];
        });

        if ($getAll && $page == 1 && auth()->user()->isAdmin()) {
            $response->push(['id' => 'all', 'text' => 'All']);
        }

        return response()->json([
            'items' => $response->reverse()->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function import(Request $request) {
        $response = $leaveBlank = [];
        $errorCount = $successCount = 0;

        $file = $request->file('xlsx');
        $type = $file->getClientOriginalExtension();

        if (!in_array($type, ['xlsx'])) {

            Import::recordImport([
                'checklist_id' => null,
                'type' => 1,
                'file_name' => $file->getClientOriginalName(),
                'success' => 0,
                'error' => 0,
                'status' => 2,
                'response' => [
                    'File is not supported. please upload xlsx.'
                ]
            ], $file);

            return response()->json(['status' => false, 'message' => 'File is not supported. please upload xlsx.']);
        }

        $expectedHeaders = [
            'first name',
            'middle name',
            'last name',
            'email',
            'employee id',
            'username',
            'phone number',
            'status',
            'password',
            'role'
        ];

        $isFileValid = false;
        $data = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\LocationImport(), $file);

        if (!isset($data[0][0][0])) {
            Import::recordImport([
                'checklist_id' => null,
                'type' => 1,
                'file_name' => $file->getClientOriginalName(),
                'success' => 0,
                'error' => 0,
                'status' => 2,
                'response' => [
                    'Uploaded file headers do not match the expected format.'
                ]
            ], $file);
            return response()->json(['status' => false, 'message' => 'Uploaded file headers do not match the expected format.']);
        }

        if (
            strtolower($data[0][0][0]) == $expectedHeaders[0] &&
            strtolower($data[0][0][1]) == $expectedHeaders[1] &&
            strtolower($data[0][0][2]) == $expectedHeaders[2] &&
            strtolower($data[0][0][3]) == $expectedHeaders[3] &&
            strtolower($data[0][0][4]) == $expectedHeaders[4] &&
            strtolower($data[0][0][5]) == $expectedHeaders[5] &&
            strtolower($data[0][0][6]) == $expectedHeaders[6] &&
            strtolower($data[0][0][7]) == $expectedHeaders[7] &&
            strtolower($data[0][0][8]) == $expectedHeaders[8] &&
            strtolower($data[0][0][9]) == $expectedHeaders[9]
        ) {
            $isFileValid = true;
        }

        if (!$isFileValid) {
            Import::recordImport([
                'checklist_id' => null,
                'type' => 1,
                'file_name' => $file->getClientOriginalName(),
                'success' => 0,
                'error' => 0,
                'status' => 2,
                'response' => [
                    'Uploaded file headers do not match the expected format.'
                ]
            ], $file);
            return response()->json(['status' => false, 'message' => 'Uploaded file headers do not match the expected format.']);
        }

        $data = array_splice($data[0], 1, count($data[0]));

        $allEmployeeIdFromSheet = array_column($data, 4);
        $allUsernameFromSheet = array_column($data, 5);
        $allPhoneNumberFromSheet = array_column($data, 6);

        $allEmployeeIdFromSheetUniqe = $allEmployeeIdFromSheet;
        $allUsernameFromSheetUniqe = $allUsernameFromSheet;
        $allPhoneNumberFromSheetUniqe = $allPhoneNumberFromSheet;

        if (count($allEmployeeIdFromSheet) != count($allEmployeeIdFromSheetUniqe)) {
            Import::recordImport([
                'checklist_id' => null,
                'type' => 1,
                'file_name' => $file->getClientOriginalName(),
                'success' => 0,
                'error' => count($allEmployeeIdFromSheet) - count($allEmployeeIdFromSheetUniqe),
                'status' => 2,
                'response' => [
                    'Employee ID exists for multiple records in sheet.'
                ]
            ], $file);

            DB::commit();
            return response()->json(['status' => false, 'message' => 'Employee ID exists for multiple records in sheet.']);
        }

        if (count($allUsernameFromSheet) != count($allUsernameFromSheetUniqe)) {
            Import::recordImport([
                'checklist_id' => null,
                'type' => 1,
                'file_name' => $file->getClientOriginalName(),
                'success' => 0,
                'error' => count($allUsernameFromSheet) - count($allUsernameFromSheetUniqe),
                'status' => 2,
                'response' => [
                    'Username exists for multiple records in sheet.'
                ]
            ], $file);

            DB::commit();
            return response()->json(['status' => false, 'message' => 'Username exists for multiple records in sheet.']);
        }

        if (count($allPhoneNumberFromSheet) != count($allPhoneNumberFromSheetUniqe)) {
            Import::recordImport([
                'checklist_id' => null,
                'type' => 1,
                'file_name' => $file->getClientOriginalName(),
                'success' => 0,
                'error' => count($allPhoneNumberFromSheet) - count($allPhoneNumberFromSheetUniqe),
                'status' => 2,
                'response' => [
                    'Phone Number exists for multiple records in sheet.'
                ]
            ], $file);

            DB::commit();
            return response()->json(['status' => false, 'message' => 'Phone Number exists for multiple records in sheet.']);
        }

        if (empty($data)) {
            Import::recordImport([
                'checklist_id' => null,
                'type' => 1,
                'file_name' => $file->getClientOriginalName(),
                'success' => 0,
                'error' => 0,
                'status' => 2,
                'response' => [
                    'File has not data.'
                ]
            ], $file);

            return response()->json(['status' => false, 'message' => 'File has not data']);
        }

        $helperRoles = Helper::$roles;
        $rolesOtherThanSystem = Role::whereNotIn('id', $helperRoles)
        ->pluck('id', 'name')
        ->mapWithKeys(fn($id, $name) => [\Illuminate\Support\Str::slug($name) => $id])
        ->toArray();

        if (!empty($rolesOtherThanSystem)) {
            $helperRoles = array_merge($helperRoles, $rolesOtherThanSystem);
        }

        $allRoles = array_keys($helperRoles);
        $allRoles = array_combine($allRoles, $allRoles);

        DB::beginTransaction();

        try {

            foreach ($data as $key => $row) {
                if (empty(trim($row[3]))) {
                    $errorCount++;
                    $response[$key] = 'Email is required at D' . ($key + 1);
                    continue;
                }

                if (empty(trim($row[5]))) {
                    $errorCount++;
                    $response[$key] = 'Username is required at F' . ($key + 1);
                    continue;
                }

                if (empty(trim($row[6]))) {
                    $errorCount++;
                    $response[$key] = 'Phone Number is required at G' . ($key + 1);
                    continue;
                }

                if (!isset($allRoles[$row[9]])) {
                    $errorCount++;
                    $response[$key] = 'Role does not exists at J' . ($key + 1);
                    continue;
                }                

                $allEmployeeId = User::withTrashed()->pluck('employee_id', 'employee_id')->toArray();
                $allUsernames = User::withTrashed()->pluck('username', 'username')->toArray();
                $allPhoneNumbers = User::withTrashed()->pluck('phone_number', 'phone_number')->toArray();

                //set true because for now all users will have employee_id
                if (true) {

                    if (empty(trim($row[4]))) {
                        $errorCount++;
                        $response[$key] = 'Employee ID is required at E' . ($key + 1);
                        continue;
                    }

                    if (in_array($row[4], $allEmployeeId)) {
                        /***
                         * Update User
                         * **/

                        if (User::withTrashed()->where('employee_id', '!=', $row[4])->where('username', $row[5])->exists()) {
                            $errorCount++;
                            $response[$key] = 'Use different username at F' . ($key + 1);
                            continue;
                        }

                        if (User::withTrashed()->where('employee_id', '!=', $row[4])->where('phone_number', $row[6])->exists()) {
                            $errorCount++;
                            $response[$key] = 'Use different phone number at G' . ($key + 1);
                            continue;
                        }

                        $currentUserRole = User::withTrashed()->where(function ($builder) use ($row) {
                            $builder->where('employee_id', $row[4]);
                        })->first()->roles()->pluck('id')->toArray();

                        $userEloquent = User::withTrashed()->where(function ($builder) use ($row) {
                            $builder->where('employee_id', $row[4]);
                        })->first();

                        $userEloquent->name = $row[0];
                        $userEloquent->middle_name = $row[1];
                        $userEloquent->last_name = $row[2];
                        $userEloquent->email = $row[3];
                        $userEloquent->employee_id = $row[4];
                        $userEloquent->username = $row[5];
                        $userEloquent->phone_number = $row[6];
                        $userEloquent->deleted_at = null;
                        $userEloquent->status = strtolower($row[7]) == 'enable' ? 1 : 0;

                        if (!empty(trim($row[8]))) {
                            $userEloquent->password = trim($row[8]);
                        }

                        $userEloquent->save();
                        $successCount++;

                        if (isset($currentUserRole[0])) {
                            if (!in_array($helperRoles[$row[9]], $currentUserRole)) {
                                /***
                                 * Role Updated
                                 * **/
                                
                                $userEloquent->syncRoles([$helperRoles[$row[9]]]);

                                /***
                                 * Role Updated
                                 * **/
                            }
                        }

                        /***
                         * Update User
                         * **/
                    } else {
                        /***
                         * Add User
                         * **/

                    if (in_array($row[5], $allUsernames)) {
                        $errorCount++;
                        $response[$key] = 'Use different username at F' . ($key + 1);
                        continue;
                    }

                    if (in_array($row[6], $allPhoneNumbers)) {
                        $errorCount++;
                        $response[$key] = 'Use different phone number at G' . ($key + 1);
                        continue;
                    }

                        $userEloquent = new User();
                        $userEloquent->name = $row[0];
                        $userEloquent->middle_name = $row[1];
                        $userEloquent->last_name = $row[2];
                        $userEloquent->email = $row[3];
                        $userEloquent->employee_id = $row[4];
                        $userEloquent->username = $row[5];
                        $userEloquent->phone_number = $row[6];
                        $userEloquent->status = strtolower($row[7]) == 'enable' ? 1 : 0;

                        if (!empty(trim($row[8]))) {
                            $userEloquent->password = trim($row[8]);
                        } else {
                            $userEloquent->password = $row[6];
                        }

                        $userEloquent->save();
                        $userEloquent->syncRoles([$helperRoles[$row[9]]]);

                        $successCount++;

                        array_push($allEmployeeId, [$row[4] => $row[4]]);
                        array_push($allUsernames, [$row[5] => $row[5]]);
                        array_push($allPhoneNumbers, [$row[6] => $row[6]]);

                        $allEmployeeId = array_filter($allEmployeeId);
                        $allUsernames = array_filter($allUsernames);
                        $allPhoneNumbers = array_filter($allPhoneNumbers);                        

                        /***
                         * Add User
                         * **/
                    }
                } else {
                    if (in_array($row[6], $allPhoneNumbers)) {
                        /***
                         * Update User
                         * **/

                        if (User::withTrashed()->where('phone_number', '!=', $row[6])->where('username', $row[5])->exists()) {
                            $errorCount++;
                            $response[$key] = 'Use different username at F' . ($key + 1);
                            continue;
                        }

                        $currentUserRole = User::withTrashed()->where(function ($builder) use ($row) {
                            $builder->where('phone_number', $row[6]);
                        })->first()->roles()->pluck('id')->toArray();

                        $userEloquent = User::withTrashed()->where(function ($builder) use ($row) {
                            $builder->where('phone_number', $row[6]);
                        })->first();

                        $userEloquent->name = $row[0];
                        $userEloquent->middle_name = $row[1];
                        $userEloquent->last_name = $row[2];
                        $userEloquent->email = $row[3];
                        $userEloquent->employee_id = $row[4];
                        $userEloquent->username = $row[5];
                        $userEloquent->phone_number = $row[6];
                        $userEloquent->deleted_at = null;
                        $userEloquent->status = strtolower($row[7]) == 'enable' ? 1 : 0;

                        if (!empty(trim($row[8]))) {
                            $userEloquent->password = trim($row[8]);
                        }

                        $userEloquent->save();
                        $successCount++;

                        if (isset($currentUserRole[0])) {
                            if (!in_array($helperRoles[$row[9]], $currentUserRole)) {
                                /***
                                 * Role Updated
                                 * **/

                                $userEloquent->syncRoles([$helperRoles[$row[9]]]);

                                /***
                                 * Role Updated
                                 * **/
                            }
                        }

                        /***
                         * Update User
                         * **/
                    } else {
                        /***
                         * Add User
                         * **/

                    if (in_array($row[5], $allUsernames)) {
                        $errorCount++;
                        $response[$key] = 'Use different username at F' . ($key + 1);
                        continue;
                    }

                    if (in_array($row[6], $allPhoneNumbers)) {
                        $errorCount++;
                        $response[$key] = 'Use different phone number at G' . ($key + 1);
                        continue;
                    }

                        $userEloquent = new User();
                        $userEloquent->name = $row[0];
                        $userEloquent->middle_name = $row[1];
                        $userEloquent->last_name = $row[2];
                        $userEloquent->email = $row[3];
                        $userEloquent->employee_id = $row[4];
                        $userEloquent->username = $row[5];
                        $userEloquent->phone_number = $row[6];
                        $userEloquent->status = strtolower($row[7]) == 'enable' ? 1 : 0;

                        if (!empty(trim($row[8]))) {
                            $userEloquent->password = trim($row[8]);
                        }

                        $userEloquent->save();
                        $userEloquent->syncRoles([$helperRoles[$row[9]]]);

                        $successCount++;

                        array_push($allEmployeeId, [$row[4] => $row[4]]);
                        array_push($allUsernames, [$row[5] => $row[5]]);
                        array_push($allPhoneNumbers, [$row[6] => $row[6]]);

                        $allEmployeeId = array_filter($allEmployeeId);
                        $allUsernames = array_filter($allUsernames);
                        $allPhoneNumbers = array_filter($allPhoneNumbers);

                        /***
                         * Add User
                         * **/
                    }
                }
            }
            
            Import::recordImport([
                'checklist_id' => null,
                'type' => 1,
                'file_name' => $file->getClientOriginalName(),
                'success' => $successCount,
                'error' => $errorCount,
                'status' => $successCount == 0 ? 2 : (
                    $errorCount > 0 ? 3 : 1
                ),
                'response' => $response,
                'leave_blank' => $leaveBlank
            ], $file, true);
            
            

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Users list updated successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERROR ON USER IMPORT:' . $e->getMessage() . ' ON LINE ' . $e->getLine());
            return response()->json(['status' => false, 'message' => 'Something went wrong.']);
        }
    }

    public function export(Request $request) {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\UsersExport, 'users.xlsx');
    }
}