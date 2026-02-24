<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Imports\LocationImport;
use Illuminate\Http\Request;
use App\Models\ModelType;
use App\Models\StoreType;
use App\Helpers\Helper;
use App\Models\Import;
use App\Models\Store;
use App\Models\City;
use App\Models\User;
use App\Models\PricingTier;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->ajax($request);
        }

        $page_title = "Stores";
        $page_description = 'Manage stores here';

        $storeTypes = StoreType::all();
        $modelTypes = ModelType::all();
        $pricingTiers = PricingTier::all();

        // Filters are still resolved for displaying currently selected values
        $stateFilter = City::select('city_state')->where('city_state', request('filter_state'))->first();
        $cityFilter = City::select('city_name')->where('city_id', request('filter_city'))->first();
        $domFilter = User::select('employee_id', 'name', 'middle_name', 'last_name')->where('id', request('filter_dom'))->first();

        return view('stores.index', compact('page_title', 'page_description', 'storeTypes', 'modelTypes', 'pricingTiers', 'stateFilter', 'cityFilter', 'domFilter'));
    }

    /**
     * Stores listing for DataTables (Yajra) server-side processing.
     */
    public function ajax(Request $request)
    {
        $storesQuery = Store::with(['thecity', 'storetype', 'modeltype', 'pricingTier'])
            ->when(!empty($request->filter_general_search), function ($builder) use ($request) {
                $term = $request->filter_general_search;
                $builder->where(function ($q) use ($term) {
                    $q->where('code', 'LIKE', "%{$term}%")
                      ->orWhere('name', 'LIKE', "%{$term}%")
                      ->orWhere('mobile', 'LIKE', "%{$term}%")
                      ->orWhere('email', 'LIKE', "%{$term}%")
                      ->orWhere('address1', 'LIKE', "%{$term}%")
                      ->orWhere('address2', 'LIKE', "%{$term}%")
                      ->orWhere('whatsapp', 'LIKE', "%{$term}%");
                });
            })
            ->when(!empty($request->filter_store_type) && $request->filter_store_type != 'all', function ($builder) use ($request) {
                $builder->where('store_type', $request->filter_store_type);
            })
            ->when(!empty($request->filter_start_date), function ($builder) use ($request) {
                 // Assuming there might be a date filter in future or if user copied it from orders
            })
             ->when(!empty($request->filter_end_date), function ($builder) use ($request) {
                 // Same as above
            })
             ->when(!empty($request->filter_pricing_tier) && $request->filter_pricing_tier != 'all', function ($builder) use ($request) {
                $builder->where('pricing_tier_id', $request->filter_pricing_tier);
            })
            ->when(!empty($request->filter_state) && $request->filter_state != 'all', function ($builder) use ($request) {
                $builder->whereHas('thecity', function ($innerBuilder) use ($request) {
                    $innerBuilder->where('city_state', $request->filter_state);
                });
            })
            ->when(!empty($request->filter_mt) && $request->filter_mt != 'all', function ($builder) use ($request) {
                $builder->where('model_type', $request->filter_mt);
            })
            ->when(!empty($request->filter_city) && $request->filter_city != 'all', function ($builder) use ($request) {
                $builder->whereHas('thecity', function ($innerBuilder) use ($request) {
                    $innerBuilder->where('city_id', $request->filter_city);
                });
            });

        return datatables()
            ->eloquent($storesQuery)
            ->addColumn('state', function ($row) {
                return $row->thecity->city_state ?? '';
            })
            ->addColumn('city', function ($row) {
                return $row->thecity->city_name ?? '';
            })
            ->addColumn('store_type_name', function ($row) {
                return $row->storetype->name ?? '';
            })
            ->addColumn('model_type_name', function ($row) {
                return $row->modeltype->name ?? '';
            })
            ->addColumn('pricing_tier_name', function ($row) {
                return $row->pricingTier->name ?? 'N/A';
            })
            ->addColumn('action', function ($row) {
                $actions = '';

                if (auth()->user()->can('stores.show')) {
                    $actions .= '<a href="' . route('stores.show', $row->id) . '" class="btn btn-info btn-sm me-1" title="View">Show</a>';
                }

                if (auth()->user()->can('stores.edit')) {
                    $actions .= '<a href="' . route('stores.edit', $row->id) . '" class="btn btn-warning btn-sm me-1" title="Edit">Edit</a>';
                }

                if (auth()->user()->can('stores.destroy')) {
                    $actions .= '<form method="POST" action="' . route('stores.destroy', $row->id) . '" style="display:inline-block">'
                        . csrf_field()
                        . method_field('DELETE')
                        . '<button type="submit" class="btn btn-danger btn-sm deleteGroup" title="Delete">Delete</button>'
                        . '</form>';
                }

                $actions .= '<a href="' . route('ledger.show', $row->id) . '" class="btn btn-primary btn-sm" target="_blank">
                    Ledger
                </a>';

                return $actions;
            })
            ->editColumn('open_time', function ($row) {
                return $row->open_time;
            })
            ->editColumn('close_time', function ($row) {
                return $row->close_time;
            })
            ->addColumn('address_1', function ($row) {
                return $row->address1 . ' ' . $row->address2;
            })
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make(true);
    }

    public function create()
    {
        $page_title = "Create Store";
        $storeTypes = StoreType::all();
        $modelTypes = ModelType::all();
        $pricingTiers = PricingTier::all();

        return view('stores.create', compact('page_title', 'storeTypes', 'modelTypes', 'pricingTiers'));
    }

    public function store(Request $request) {

        $request->validate([
            'store_type' => 'required',
            'model_type' => $request->store_type == 1 ? 'required' : 'nullable',
            'name' => [
                'required',
                Rule::unique('stores', 'name'),
            ],
            'code' => [
                'required',
                Rule::unique('stores', 'code'),
            ],
            'open_time' => 'required',
            'close_time' => 'required',
            'city' => 'required',
            'employees' => 'required|array|min:1'
        ]);

        Store::create([
            'store_type' => $request->store_type,
            'model_type' => $request->model_type,
            'email' => $request->email,
            'name' => $request->name,
            'code' => $request->code,
            'address1' => is_null($request->address1) ? '' : $request->address1,
            'address2' => $request->address2,
            'block' => $request->block,
            'street' => $request->street,
            'landmark' => $request->landmark,
            'mobile' => $request->mobile_type,
            'whatsapp' => $request->whatsapp_type,
            'location' => $request->location,
            'open_time' => date("h:i A", strtotime($request->open_time)),
            'close_time' => date("h:i A", strtotime($request->close_time)),
            'ops_start_time' => date("h:i A", strtotime($request->ops_start_time)),
            'ops_end_time' => date("h:i A", strtotime($request->ops_end_time)),
            'latitude' => $request->map_latitude,
            'longitude' => $request->map_longitude,
            'location_url' => $request->location_url,
            'map_latitude' => $request->map_latitude,
            'map_longitude' => $request->map_longitude,
            'city' => $request->city,
            'pricing_tier_id' => $request->pricing_tier_id,
            // 'dom_id' => $request->dom_id,
            'upi_handle' => $request->upi_handle
        ]);

        $storeEloquent = Store::where('code', $request->code)->first();
        if($storeEloquent){
             $storeEloquent->users()->sync($request->employees);
             
             // Generate QR Code
             if (!empty($request->upi_handle)) {
                 \App\Helpers\QrCodeHelper::generateQrCode($request->upi_handle, 'store_' . $storeEloquent->id . '_qr.png');
             }
        }

        return redirect()->route('stores.index')->with('success', 'Location created successfully');
    }

    public function show(Store $store)
    {
        $page_title = "Store Details";
        $store->load(['thecity', 'storetype', 'modeltype', 'pricingTier', 'users']);

        return view('stores.show', compact('page_title', 'store'));
    }

    public function edit(Request $request, $id) {
        $page_title = "Locations";
        $store = Store::find($id);
        $storeTypes = StoreType::all();
        $modelTypes = ModelType::all();
        $pricingTiers = PricingTier::all();

        return view( 'stores.edit', compact( 'page_title', 'store', 'storeTypes', 'modelTypes', 'pricingTiers' ) );
    }

    public function update(Request $request, $stores) {

        $request->validate([
            'store_type' => "required",
            'model_type' => $request->store_type == 1 ? "required" : "nullable",
            'name' => [
                'required',
                Rule::unique('stores', 'name')->ignore($stores),
            ],
            'code' => [
                'required',
                Rule::unique('stores', 'code')->ignore($stores),
            ],
            'open_time' => 'required',
            'close_time' => 'required',
            'city' => 'required',
            'employees' => 'required|array|min:1'
        ]);

        Store::where('id', $stores)->update([
            'store_type' => $request->store_type,
            'model_type' => $request->model_type,
            'email' => $request->email,
            'name' => $request->name,
            'code' => $request->code,
            'address1' => is_null($request->address1) ? '' : $request->address1,
            'address2' => $request->address2,
            'block' => $request->block,
            'street' => $request->street,
            'landmark' => $request->landmark,
            'mobile' => $request->mobile_type,
            'whatsapp' => $request->whatsapp_type,
            'location' => $request->location,
            'open_time' => date("h:i A", strtotime($request->open_time)),
            'close_time' => date("h:i A", strtotime($request->close_time)),
            'ops_start_time' => date("h:i A", strtotime($request->ops_start_time)),
            'ops_end_time' => date("h:i A", strtotime($request->ops_end_time)),
            'latitude' => $request->map_latitude,
            'longitude' => $request->map_longitude,
            'location_url' => $request->location_url,
            'map_latitude' => $request->map_latitude,
            'map_longitude' => $request->map_longitude,
            'city' => $request->city,
            'pricing_tier_id' => $request->pricing_tier_id,
            // 'dom_id' => $request->dom_id,
            'upi_handle' => $request->upi_handle
        ]);

        $storeEloquent = Store::find($stores);
        if($storeEloquent){
             $storeEloquent->users()->sync($request->employees);
             
             // Generate QR Code
             if (!empty($request->upi_handle)) {
                 \App\Helpers\QrCodeHelper::generateQrCode($request->upi_handle, 'store_' . $storeEloquent->id . '_qr.png');
             }
        }

        return redirect()->route('stores.index')->with('success', 'Location updated successfully');
    }

    public function destroy(Store $stores, $id) {
        $stores = Store::find($id);

        $stores->delete();
        return redirect()->route('stores.index')->with('success', 'Location deleted successfully');        
    }

    public function select2List(Request $request) {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;
        $getAll = $request->getall;
    
        $query = Store::query();
    
        if (!empty($queryString)) {
            $query->where('name', 'LIKE', "%{$queryString}%")
            ->orWhere('code', 'LIKE', "%{$queryString}%");
        }

        if (!auth()->user()->isAdmin() && $request->strict_stores != 1) {
            $query->where('dom_id', auth()->user()->id);
        }

        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'text' => "{$item->code} - $item->name"
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

    public function stateLists(Request $request) {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $limit = 10;
        $getAll = $request->getall;
    
        $query = City::query();
    
        if (!empty($queryString)) {
            $query->where('city_state', 'LIKE', "%{$queryString}%");
        }

        $query = $query->groupBy('city_state');
    
        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->city_state,
                'text' => $item->city_state
            ];
        });

        if ($getAll && $page == 1) {
            $response->push(['id' => 'all', 'text' => 'All']);
        }

        return response()->json([
            'items' => $response->reverse()->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function cityLists(Request $request) {
        $queryString = trim($request->searchQuery);
        $page = $request->input('page', 1);
        $state = $request->state;
        $limit = 10;
        $getAll = $request->getall;
    
        $query = City::query();
    
        if (!empty($queryString)) {
            $query->where('city_name', 'LIKE', "%{$queryString}%");
        }
    
        if (!empty($state)) {
            if ($state !== 'all') {
                $query->where('city_state', $state);
            }
        }

        $data = $query->paginate($limit, ['*'], 'page', $page);
        $response = $data->map(function ($item) {
            return [
                'id' => $item->city_id,
                'text' => $item->city_name
            ];
        });

        if ($getAll && $page == 1) {
            $response->push(['id' => 'all', 'text' => 'All']);
        }

        return response()->json([
            'items' => $response->reverse()->values(),
            'pagination' => [
                'more' => $data->hasMorePages()
            ]
        ]);
    }

    public function importStores(Request $request) {
        $file = $request->file('xlsx');
        $data = Excel::toArray(new LocationImport(),$file);
        $response = [];
        $successCount = $errorCount = 0;

        $expectedHeaders = [
            'STORE NAME',
            'TYPE',
            'CITY',
            'STATE',
            'MANAGER FIRST NAME',
            'MANAGER MIDDLE NAME',
            'MANAGER LAST NAME',
            'OPS MGR',
            'OPS HEAD',
            'MODEL',
            'MANAGER ID',
            'MANAGER MOBILE',
            'ADDRESS 1',
            'ADDRESS 2',
            'BLOCK',
            'STREET',
            'LANDMARK',
            'STORE MOBILE',
            'STORE WHATSAPP',
            'LATITUDE',
            'LONGITUDE',
            'LOCATION URL',
            'STORE OPENING TIME',
            'STORE CLOSING TIME',
            'OPERATION START TIME',
            'OPERATION END TIME',
            'STORE MAIL ID'
        ];

        DB::beginTransaction();

        try {
            if (!empty($data) && isset($data[0])) {
                foreach ($data[0] as $key => $row) {
                    if ($key) {

                        $codeName = explode(' ', $row[0]);
                        $city = City::where(DB::raw('LOWER(city_name)'), strtolower($row[2]))
                        ->where(DB::raw('LOWER(city_state)'), strtolower($row[3]))
                        ->first();

                        if (isset($codeName[0]) && isset($codeName[1])) {

                            $toBeAdded = [];

                            if (!$city) {
                                $errorCount++;
                                $response[$key] = 'City or state is invalid at C' . ($key + 1);
                                continue;
                            }

                            if (StoreType::where(DB::raw('LOWER(name)'), strtolower($row[1]))->doesntExist()) {
                                $errorCount++;
                                $response[$key] = 'Store type is invalid at B' . ($key + 1);
                                continue;
                            }

                            if (ModelType::where(DB::raw('LOWER(name)'), strtolower($row[9]))->doesntExist()) {
                                $errorCount++;
                                $response[$key] = 'Store model type is invalid at J' . ($key + 1);
                                continue;
                            }

                            $theCurrentDom = null;

                            if (empty($row[10])) {
                                $errorCount++;
                                $response[$key] = 'MANAGER does not exists at K' . ($key + 1);
                                continue;
                            } else {
                                $explodedDomString = explode('_', str_replace(' ', '', $row[10]));
                                if (isset($explodedDomString[0])) {
                                    $currentDom = User::withTrashed()->where('employee_id', $explodedDomString[0])->first();

                                    if ($currentDom) {
                                        if (!empty($row[4])) {
                                            $currentDom->name = $row[4];
                                        }

                                        if (!empty($row[5])) {
                                            $currentDom->middle_name = $row[5];
                                        }

                                        if (!empty($row[6])) {
                                            $currentDom->last_name = $row[6];
                                        }                                        

                                        if (!empty($row[11])) {
                                            if (User::withTrashed()->where('employee_id', '!=', $explodedDomString[0])->where('phone_number', $row[11])->exists()) {
                                                $errorCount++;
                                                $response[$key] = 'Use different phone number at L' . ($key + 1);
                                                continue;
                                            } else {
                                                $currentDom->phone_number = $row[11];
                                                $currentDom->password = $row[11];
                                            }
                                        } else {
                                            $errorCount++;
                                            $response[$key] = 'Phone number is required at L' . ($key + 1);
                                            continue;
                                        }

                                        $theCurrentDom = $currentDom->id;
                                        $currentDom->save();
                                    } else {
                                        $currentDom = new User();
                                        $currentDom->employee_id = $explodedDomString[0];

                                        if (!empty($row[4])) {
                                            $currentDom->name = $row[4];
                                        } else {
                                            $errorCount++;
                                            $response[$key] = 'First name is required E' . ($key + 1);
                                            continue;
                                        }

                                        if (!empty($row[5])) {
                                            $currentDom->middle_name = $row[5];
                                        }

                                        if (!empty($row[6])) {
                                            $currentDom->last_name = $row[6];
                                        }

                                        if (!empty($row[11])) {
                                            if (User::withTrashed()->where('phone_number', $row[11])->exists()) {
                                                $errorCount++;
                                                $response[$key] = 'Use different phone number at L' . ($key + 1);
                                                continue;
                                            } else {
                                                $currentDom->phone_number = $row[11];
                                                $currentDom->password = $row[11];
                                            }
                                        } else {
                                            $errorCount++;
                                            $response[$key] = 'Phone number is required at L' . ($key + 1);
                                            continue;
                                        }

                                        $currentDom->save();
                                        $theCurrentDom = $currentDom->id;
                                        $currentDom->syncRoles([Helper::$roles['divisional-operations-manager']]);
                                        
                                    }

                                } else {
                                    $errorCount++;
                                    $response[$key] = 'MANAGER does not exists at K' . ($key + 1);
                                    continue;
                                }
                            }                            
                            

                            if (isset($row[12])) {
                                $toBeAdded['address1'] = $row[12];
                            }

                            if (isset($row[13])) {
                                $toBeAdded['address2'] = $row[13];
                            }

                            if (isset($row[14])) {
                                $toBeAdded['block'] = $row[14];
                            }

                            if (isset($row[15])) {
                                $toBeAdded['street'] = $row[15];
                            }

                            if (isset($row[16])) {
                                $toBeAdded['landmark'] = $row[16];
                            }

                            if (isset($row[17])) {
                                $toBeAdded['mobile'] = $row[17];
                            }

                            if (isset($row[18])) {
                                $toBeAdded['whatsapp'] = $row[18];
                            }

                            if (isset($row[19])) {
                                $toBeAdded['latitude'] = $row[19];
                            }

                            if (isset($row[20])) {
                                $toBeAdded['longitude'] = $row[20];
                            }

                            if (isset($row[21])) {
                                $toBeAdded['location_url'] = $row[21];
                            }
                            
                            if (isset($row[22]) && !empty(trim($row[22]))) {
                                $cellValue = trim($row[22]);

                                if (is_numeric($cellValue)) {
                                    $toBeAdded['open_time'] = Date::excelToDateTimeObject(floatval($cellValue))->format('h:i A');
                                } else {
                                    $time = strtotime($cellValue);
                                    if ($time !== false) {
                                        $toBeAdded['open_time'] = date('h:i A', $time);
                                    } else {
                                        $toBeAdded['open_time'] = '12:00 AM';
                                    }
                                }
                            } else {
                                $toBeAdded['open_time'] = '12:00 AM';
                            }

                            if (isset($row[23]) && !empty(trim($row[23]))) {
                                $cellValue = trim($row[23]);

                                if (is_numeric($cellValue)) {
                                    $toBeAdded['close_time'] = Date::excelToDateTimeObject(floatval($cellValue))->format('h:i A');
                                } else {
                                    $time = strtotime($cellValue);
                                    if ($time !== false) {
                                        $toBeAdded['close_time'] = date('h:i A', $time);
                                    } else {
                                        $toBeAdded['close_time'] = '11:59 PM';
                                    }
                                }
                            } else {
                                $toBeAdded['close_time'] = '11:59 PM';
                            }

                            if (isset($row[24]) && !empty(trim($row[24]))) {
                                $cellValue = trim($row[24]);

                                if (is_numeric($cellValue)) {
                                    $toBeAdded['ops_start_time'] = Date::excelToDateTimeObject(floatval($cellValue))->format('h:i A');
                                } else {
                                    $time = strtotime($cellValue);
                                    if ($time !== false) {
                                        $toBeAdded['ops_start_time'] = date('h:i A', $time);
                                    } else {
                                        $toBeAdded['ops_start_time'] = '12:00 AM';
                                    }
                                }
                            } else {
                                $toBeAdded['ops_start_time'] = '12:00 AM';
                            }
                            
                            if (isset($row[25]) && !empty(trim($row[25]))) {
                                $cellValue = trim($row[25]);

                                if (is_numeric($cellValue)) {
                                    $toBeAdded['ops_end_time'] = Date::excelToDateTimeObject(floatval($cellValue))->format('h:i A');
                                } else {
                                    $time = strtotime($cellValue);
                                    if ($time !== false) {
                                        $toBeAdded['ops_end_time'] = date('h:i A', $time);
                                    } else {
                                        $toBeAdded['ops_end_time'] = '11:59 PM';
                                    }
                                }
                            } else {
                                $toBeAdded['ops_end_time'] = '11:59 PM';
                            }

                            if (isset($row[26]) && !empty(trim($row[26]))) {
                                $toBeAdded['email'] = $row[26];
                            } else {
                                $toBeAdded['email'] = '';
                            }

                            $toBeAdded['code'] = $codeName[0];
                            $toBeAdded['name'] = implode(' ', array_splice($codeName, 1, count($codeName)));
                            $toBeAdded['store_type'] = StoreType::firstWhere(DB::raw('LOWER(name)'), strtolower($row[1]))->id ?? null;
                            $toBeAdded['model_type'] = ModelType::firstWhere(DB::raw('LOWER(name)'), strtolower($row[9]))->id ?? null;
                            $toBeAdded['city'] = $city->city_id ?? null;
                            $toBeAdded['dom_id'] = $theCurrentDom;
                            
                            Store::updateOrCreate([
                                'code' => $toBeAdded['code'],
                            ], $toBeAdded);

                            $successCount++;
                            
                        } else {
                                $errorCount++;
                                $response[$key] = 'Valid store information does not exists at A' . ($key + 1);
                                continue;
                        }
                    } else {

                        if (count($row) !== count($expectedHeaders)) {
                            Import::recordImport([
                                'checklist_id' => null,
                                'type' => 2,
                                'file_name' => $file->getClientOriginalName(),
                                'success' => 0,
                                'error' => 0,
                                'status' => 2,
                                'response' => [
                                    'Uploaded file has an incorrect number of columns.'
                                ]
                            ], $file);

                            DB::rollBack();
                            return response()->json(['status' => false, 'message' => 'Uploaded file has an incorrect number of columns.']);
                        }

                        if (!(
                               strtoupper($row[0])  == $expectedHeaders[0]
                            && strtoupper($row[1])  == $expectedHeaders[1]
                            && strtoupper($row[2])  == $expectedHeaders[2]
                            && strtoupper($row[3])  == $expectedHeaders[3]
                            && strtoupper($row[4])  == $expectedHeaders[4]
                            && strtoupper($row[5])  == $expectedHeaders[5]
                            && strtoupper($row[6])  == $expectedHeaders[6]
                            && strtoupper($row[7])  == $expectedHeaders[7]
                            && strtoupper($row[8])  == $expectedHeaders[8]
                            && strtoupper($row[9])  == $expectedHeaders[9]
                            && strtoupper($row[10]) == $expectedHeaders[10]
                            && strtoupper($row[11]) == $expectedHeaders[11]
                            && strtoupper($row[12]) == $expectedHeaders[12]
                            && strtoupper($row[13]) == $expectedHeaders[13]
                            && strtoupper($row[14]) == $expectedHeaders[14]
                            && strtoupper($row[15]) == $expectedHeaders[15]
                            && strtoupper($row[16]) == $expectedHeaders[16]
                            && strtoupper($row[17]) == $expectedHeaders[17]
                            && strtoupper($row[18]) == $expectedHeaders[18]
                            && strtoupper($row[19]) == $expectedHeaders[19]
                            && strtoupper($row[20]) == $expectedHeaders[20]
                            && strtoupper($row[21]) == $expectedHeaders[21]
                            && strtoupper($row[22]) == $expectedHeaders[22]
                            && strtoupper($row[23]) == $expectedHeaders[23]
                            && strtoupper($row[24]) == $expectedHeaders[24]
                            && strtoupper($row[25]) == $expectedHeaders[25]
                            && strtoupper($row[26]) == $expectedHeaders[26]
                        )) {
                            
                            Import::recordImport([
                                'checklist_id' => null,
                                'type' => 2,
                                'file_name' => $file->getClientOriginalName(),
                                'success' => 0,
                                'error' => 0,
                                'status' => 2,
                                'response' => [
                                    'Uploaded file headers do not match the expected format.'
                                ]
                            ], $file);

                            DB::rollBack();
                            return response()->json(['status' => false, 'message' => 'Files header are mismatching.']);
                        }
                    }
                }
            } else {
                Import::recordImport([
                    'checklist_id' => null,
                    'type' => 2,
                    'file_name' => $file->getClientOriginalName(),
                    'success' => 0,
                    'error' => 0,
                    'status' => 2,
                    'response' => [
                        'File is empty'
                    ]
                ], $file);

                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'File is empty.']);
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
                'leave_blank' => 0
            ], $file, true);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Store list updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error occured on importing the stores ' . $e->getMessage() . ' on line ' . $e->getLine());
            return response()->json(['status' => false, 'message' => 'Something went wrong!']);
        }
    }

    public function exportStores()
    {
        $stores = Store::with( [ 'thecity', 'storetype', 'modeltype', 'dom' ] )
        ->when(!empty(request('filter_location')), function ($builder) {
            $builder->where('code', request('filter_location'));
        })
        ->when(!empty(request('filter_state')) && request('filter_state') != 'all', function ($builder) {
            $builder->whereHas('thecity', function ($innerBuilder) {
                $innerBuilder->where('city_state', request('filter_state'));
            });
        })
        ->when(!empty(request('filter_city')) && request('filter_city') != 'all', function ($builder) {
            $builder->whereHas('thecity', function ($innerBuilder) {
                $innerBuilder->where('city_id', request('filter_city'));
            });
        })
        ->when(!empty(request('filter_dom')) && request('filter_dom') != 'all', function ($builder) {
            $builder->where('dom_id', request('filter_dom'));
        })
        ->get();

        return Excel::download(new \App\Exports\StoresExport($stores), 'stores.xlsx');
    }
}