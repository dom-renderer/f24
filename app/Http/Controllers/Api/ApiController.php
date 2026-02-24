<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\DeviceToken;
use App\Helpers\Helper;
use App\Models\Store;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartOtherItem;
use App\Models\Order;
use App\Models\UnitPriceTier;
use App\Models\OrderCategory;
use App\Models\OrderItem;
use App\Models\OrderProduct;
use App\Models\User;
use App\Models\OrderProductUnit;
use App\Models\OrderPaymentLog;
use App\Models\OrderCharge;
use App\Models\OrderUtencil;
use App\Models\OrderUtencilHistory;
use App\Models\Utencil;
use App\Models\UnitDiscountTier;

class ApiController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            $errorString = implode(",", $validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        if (Auth::attempt(['phone_number' => $request->phone_number, 'password' => $request->password])) {

            $user = Auth::user();
            unset($user->password);

            if ($user->status != 1) {
                return response()->json(['error' => 'Your account is disabled by the admin!'], 401);
            } else {
                $settings = \App\Models\Setting::first();

                $userAllStores = \App\Models\UserStore::select('store_id')->where('user_id', $user->id)->pluck('store_id')->toArray();
                $theStore = isset($userAllStores[0]) ? $userAllStores[0] : null;

                $success = [
                    'token' => $user->createToken(APP_NAME)->accessToken,
                    'userId' => $user->id,
                    'userDetails' => $user,
                    'system_roles' => \Spatie\Permission\Models\Role::select('id', 'name')->get()->toArray(),
                    'role' => auth()->user()->roles[0]->id,
                    'order_variables' => [
                        'sender_store_id' => Store::whereHas('storetype', function ($builder) {
                            $builder->where('name', 'factory');
                        })->value('id'),
                        'receiver_store_id' => $theStore,
                        'dealer_id' => $user->id,
                        'current_user_type' => auth()->user()->roles[0]->id == 5 ? 'dealer' : 'store',
                        'address' => Store::with('thecity')->selectRaw("id, name, code, address1, address2, block, street, landmark, city")
                        ->where('id', $theStore)
                        ->first(),
                        'cgst' => $settings->cgst_percentage ?? 0,
                        'sgst' => $settings->sgst_percentage ?? 0,
                        'order_type' => auth()->user()->roles[0]->id == 5 ? 'dealer' : (
                            in_array(auth()->user()->roles[0]->id, [3, 4]) ? (
                                \App\Models\UserStore::where('user_id', auth()->user()->id)->whereHas('store.modeltype', function ($innerBuilder) {
                                    $innerBuilder->whereIn('name', ['franchisee']);
                                })->exists() ? 'franchise' : 'company'
                            ) : 'franchise'
                        )
                    ]
                ];

                return response()->json(['success' => $success], 200);
            }
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
        }
    }

    public function stores(Request $request)
    {
        return response()->json(['success' => Store::with(['storetype', 'modeltype'])->orderBy('name', 'ASC')->get()]);
    }

    public function deviceToken(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            $errorString = implode(",", $validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        if (DeviceToken::where('token', $request->token)->exists()) {
            if (DeviceToken::where(function ($builder) {
                return $builder->whereNull('user_id')->orWhere('user_id', '');
            })->where('token', $request->token)->exists()) {

                DeviceToken::where(function ($builder) {
                    return $builder->whereNull('user_id')->orWhere('user_id', '');
                })->where('token', $request->token)->update([
                    'user_id' => $request->user_id
                ]);
            } else {
                DeviceToken::updateOrCreate([
                    'token' => $request->token
                ], [
                    'user_id' => $request->user_id,
                    'token' => $request->token
                ]);
            }
        } else {
            DeviceToken::updateOrCreate([
                'user_id' => $request->user_id,
                'token' => $request->token
            ]);
        }

        return response()->json(['success' => "Device token saved successfully."]);
    }

    public function removeDeviceToken(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            $errorString = implode(",", $validator->messages()->all());
            return response()->json(['error' => $errorString], 401);
        }

        DeviceToken::where('user_id', $request->user_id)->where('token', $request->token)->update([
            'user_id' => null
        ]);

        return response()->json(['success' => "Device token removed from user successfully."]);
    }

    public function users(Request $request)
    {
        $fixRoles = [
            1,
            2,
            3,
            4,
            5
        ];

        $data = User::select('id', 'name', 'middle_name', 'last_name', 'email', 'employee_id')
            ->when($request->filled('role_id'), function ($query) use ($request, $fixRoles) {
                $query->whereHas('roles', function ($roleQuery) use ($request, $fixRoles) {
                    $roleQuery->whereIn('id', $fixRoles);
                });
            })
            ->where('status', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim($user->name . ' ' . $user->middle_name . ' ' . $user->last_name),
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                ];
            });

        return response()->json(['success' => $data]);
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $storeId = $request->input('store_id', null);
        $filter = 'all';

        $query = Order::with(['items.product', 'senderStore'])
        ->where(function ($q) {
            if (request('user_type') == 3 || request('user_type') == 4) {
                $q->whereHas('receiverStore.users', function ($iQ) {
                    $iQ->where('user_id', auth()->user()->id);
                });
            } else if (request('user_type') == 5) {
                $q->where('dealer_id', auth()->user()->id);
            } else if (request('user_type') == 6) {
                $q->where('delivery_user', auth()->user()->id);
            }
        });

        if ($filter === 'today') {
            $query->whereDate('delivery_schedule_from', today());
        } elseif ($filter === 'week') {
            $query->whereBetween('delivery_schedule_from', [now()->startOfWeek(), now()->endOfWeek()]);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();
        $from = date('Y-m-d 00:00:00', strtotime($request->from_date));
        $to   = date('Y-m-d 23:59:59', strtotime($request->to_date));

        $upcomingDelivery = Order::with(['items.product', 'senderStore'])
            ->where(function ($q) use ($from, $to)  {
                if (request('user_type') == 3 || request('user_type') == 4) {
                    $q->whereHas('receiverStore.users', function ($iQ) {
                        $iQ->where('users.id', auth()->user()->id);
                    })
                    ->whereIn('status', [Order::STATUS_DISPATCHED, Order::STATUS_DISPATCHED])
                    ->when($from, function ($q) use ($from) {
                        $q->where('created_at', '>=', $from);
                    })
                    ->when($to, function ($q) use ($to) {
                        $q->where('created_at', '<=', $to);
                    });
                } else if (request('user_type') == 5) {
                    $q->where('dealer_id', auth()->user()->id)
                    ->whereIn('status', [Order::STATUS_APPROVED, Order::STATUS_DISPATCHED])
                    ->when($from, function ($q) use ($from) {
                        $q->where('created_at', '>=', $from);
                    })
                    ->when($to, function ($q) use ($to) {
                        $q->where('created_at', '<=', $to);
                    });
                } else if (request('user_type') == 6) {
                    $q->where('delivery_user', auth()->user()->id)
                    ->whereIn('status', [Order::STATUS_APPROVED, Order::STATUS_DISPATCHED])
                    ->when($from, function ($q) use ($from) {
                        $q->where('delivery_schedule_from', '>=', $from);
                    })
                    ->when($to, function ($q) use ($to) {
                        $q->where('delivery_schedule_to', '<=', $to);
                    });
                }
            })
            ->latest()
            ->get();

        $pendingApproval = Order::with(['items.product.images', 'items.unit'])
            ->where(function ($q) use ($from, $to)  {
                if (request('user_type') == 3 || request('user_type') == 4) {
                    $q->whereHas('receiverStore.users', function ($iQ) {
                        $iQ->where('users.id', auth()->user()->id);
                    })
                    ->whereIn('status', [Order::STATUS_PENDING])
                    ->when($from, function ($q) use ($from) {
                        $q->where('created_at', '>=', $from);
                    })
                    ->when($to, function ($q) use ($to) {
                        $q->where('created_at', '<=', $to);
                    });
                } else if (request('user_type') == 5) {
                    $q->where('dealer_id', auth()->user()->id)
                    ->whereIn('status', [Order::STATUS_PENDING])
                    ->when($from, function ($q) use ($from) {
                        $q->where('created_at', '>=', $from);
                    })
                    ->when($to, function ($q) use ($to) {
                        $q->where('created_at', '<=', $to);
                    });
                } else if (request('user_type') == 6) {
                    $q->where('delivery_user', auth()->user()->id)
                    ->whereIn('status', [Order::STATUS_PENDING])
                    ->when($from, function ($q) use ($from) {
                        $q->where('created_at', '>=', $from);
                    })
                    ->when($to, function ($q) use ($to) {
                        $q->where('created_at', '<=', $to);
                    });
                }
            })
            ->latest()
            ->get();

        $recentlyDelivered = Order::query()
            ->where(function ($q) use ($from, $to) {
                if (request('user_type') == 3 || request('user_type') == 4) {
                    $q->whereHas('receiverStore.users', function ($iQ) {
                        $iQ->where('users.id', auth()->user()->id);
                    })
                    ->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_DELIVERED])
                    ->when($from, function ($q) use ($from) {
                        $q->where('delivered_at', '>=', $from);
                    })
                    ->when($to, function ($q) use ($to) {
                        $q->where('delivered_at', '<=', $to);
                    });
                } else if (request('user_type') == 5) {
                    $q->where('dealer_id', auth()->user()->id)
                    ->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_DELIVERED])
                    ->when($from, function ($q) use ($from) {
                        $q->where('delivered_at', '>=', $from);
                    })
                    ->when($to, function ($q) use ($to) {
                        $q->where('delivered_at', '<=', $to);
                    });
                } else if (request('user_type') == 6) {
                    $q->where('delivery_user', auth()->user()->id)
                    ->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_DELIVERED])
                    ->when($from, function ($q) use ($from) {
                        $q->where('delivered_at', '>=', $from);
                    })
                    ->when($to, function ($q) use ($to) {
                        $q->where('delivered_at', '<=', $to);
                    });
                }
            })
            ->orderBy('delivered_at', 'desc')
            ->get();

        $weeklyVolume = [];
        $startOfWeek = now()->startOfWeek();
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $count = Order::where('receiver_store_id', $storeId)
                ->whereDate('created_at', $date)
                ->count();
            $weeklyVolume[] = [
                'day' => $date->format('D'),
                'date' => $date->format('Y-m-d'),
                'count' => $count
            ];
        }

        return response()->json([
            'status' => true,
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'store' => $user->store->name ?? 'N/A'
                ],
                'currency_symbol' => Helper::defaultCurrencySymbol(),
                'upcoming_delivery' => $upcomingDelivery ? $this->formatOrderSummary($upcomingDelivery) : null,
                'pending_approval' => $pendingApproval ? $this->formatOrderSummary($pendingApproval) : null,
                'recently_delivered' => $recentlyDelivered ? $this->formatOrderSummary($recentlyDelivered) : null,
                'weekly_volume' => $weeklyVolume
            ]
        ]);
    }

    public function categories(Request $request)
    {
        $categories = OrderCategory::query()
            ->orderBy('name')
            ->whereHas('products')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'children' => []
            ]);

        return response()->json([
            'status' => true,
            'data' => $categories
        ]);
    }

    public function products(Request $request)
    {
        $user = Auth::user();
        $categoryId = $request->input('category_id');
        $search = $request->input('search');
        $theQty = $request->input('quantity', 1);

        $query = OrderProduct::with(['category', 'units.unit', 'images', 'taxSlab'])
            ->where('status', 1);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->get();

        $recSto = Store::where('id', request('receiver_store_id'))->value('pricing_tier_id') ?? null;
        $grouped = $products->groupBy('category_id');
        $result = [];

        $setting = \App\Models\Setting::first();
        $cgstPercentage = $setting->cgst_percentage ?? 0;
        $sgstPercentage = $setting->sgst_percentage ?? 0;
        $finalGst = $cgstPercentage + $sgstPercentage;
        $isCompanyStore = false;

        if (Store::where('id', request('receiver_store_id'))->whereHas('storetype', function ($builder) {
            $builder->where('name', 'store');
        })->whereHas('modeltype', function ($builder) {
            $builder->whereIn('name', ['company']);
        })->exists()) {
            $isCompanyStore = true;
        }

        foreach ($grouped as $catId => $prods) {
            $category = OrderCategory::find($catId);
            $items = [];
            foreach ($prods as $product) {
                $units = [];
                foreach ($product->units as $pu) {
                    // For catalog listing we consider base (qty=1) price
                    $price = $price1 = $this->doNotCalculateDiscount($product->id, $pu->unit_id, $recSto, $theQty);
                    
                    $discountPricing = UnitDiscountTier::where('pricing_tier_id', $recSto)->where('product_id', $product->id)->where('product_unit_id', $pu->unit_id)->where('status', 1)->orderBy('min_qty', 'ASC')->get()->map(function ($el) use ($price, $price1, $finalGst, $isCompanyStore, $setting) {

                        $decimalDiscount = 1 - ($finalGst / 100);

                        $price_after_discount = ((
                            ($el->discount_type == 0 ? (
                                $price1 - (($price1 * $el->discount_amount) / 100)
                            ) : (
                                $price1 - $el->discount_amount
                            ))
                        ));

                        if ($isCompanyStore && isset($setting->company_store_discount) && is_numeric($setting->company_store_discount) && $setting->company_store_discount > 0) {
                            $price_after_discount = $price_after_discount - (($price_after_discount * $setting->company_store_discount) / 100);
                        }

                        $price_after_discount2 = $price_after_discount;
                        $price_after_discount *= $decimalDiscount;

                        return [
                            'id' => $el->id,
                            'min_qty' => $el->min_qty,
                            'max_qty' => $el->max_qty,
                            'discount_type' => $el->discount_type == 0 ? 'percentage' : 'fixed',
                            'discount_amount' => $el->discount_amount,
                            'price_after_discount_with_gst' => (string) $price_after_discount2,
                            'price_after_discount_without_gst' => (string) $price_after_discount
                        ];
                    });

                    if ($isCompanyStore && isset($setting->company_store_discount) && is_numeric($setting->company_store_discount) && $setting->company_store_discount > 0) {
                        $price = $price - (($price * $setting->company_store_discount) / 100);
                    }

                    $excPrice = ($price * $finalGst) / 100;

                    $units[] = [
                        'unit_id' => $pu->unit_id,
                        'unit_name' => $pu->unit->name ?? '',
                        'price' => (string)$price,
                        'price_included_gst' => (string)$price,
                        'price_excluded_gst' => (string)($price - $excPrice),
                        'default_price' => (string)$pu->price,
                        'pricing_tiers' => $discountPricing
                    ];
                }
                $items[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'description' => is_null($product->description) ? '' : $product->description,
                    'image' => $product->images->first() ? asset('storage/order-product-images/' . $product->images->first()->image_path) : null,
                    'units' => $units,
                    'tax_slab_id' => $product->tax_slab_id,
                    'taxSlab' => $product->taxSlab
                ];
            }
            $result[] = [
                'category_id' => $catId,
                'category_name' => $category->name ?? 'Uncategorized',
                'products' => $items
            ];
        }

        return response()->json([
            'status' => true,
            'currency_symbol' => Helper::defaultCurrencySymbol(),
            'data' => $result
        ]);
    }

    public function placeOrder(Request $request, \App\Services\LedgerService $ledgerService)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'nullable|exists:stores,id',
            'sender_store_id' => 'nullable|exists:stores,id',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:order_products,id',
            'items.*.unit_id' => 'required|exists:order_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'order_type' => 'required|in:company,franchise,dealer',
            'delivery_date' => 'required|date|after_or_equal:today',
            'handling_instructions' => 'nullable|array',
            'handling_note' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:1000',
            'collect_on_delivery' => 'nullable|boolean',
            'for_customer' => 'nullable|boolean',
            'customer_email' => 'nullable|email',
            'customer_phone_number' => 'nullable|string',
            'billing_name' => 'nullable|string|max:255',
            'billing_contact_number' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email|max:255',
            'billing_address_1' => 'nullable|string|max:500',
            'billing_address_2' => 'nullable|string|max:500',
            'billing_pincode' => 'nullable|string|max:10',
            'billing_gst_in' => 'nullable|string|max:50',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_contact_number' => 'nullable|string|max:20',
            'shipping_email' => 'nullable|email|max:255',
            'shipping_address_1' => 'nullable|string|max:500',
            'shipping_address_2' => 'nullable|string|max:500',
            'shipping_pincode' => 'nullable|string|max:10',
            'shipping_gst_in' => 'nullable|string|max:50',
            'billing_latitude' => 'nullable|numeric',
            'billing_longitude' => 'nullable|numeric',
            'billing_google_map_link' => 'nullable|string',
            'shipping_latitude' => 'nullable|numeric',
            'shipping_longitude' => 'nullable|numeric',
            'shipping_google_map_link' => 'nullable|string',
            'additional_charges' => 'nullable|array',
            'additional_charges.*.title' => 'required_with:additional_charges|string|max:255',
            'additional_charges.*.amount' => 'required_with:additional_charges|numeric|min:0',
            'other_items' => 'nullable|array',
            'other_items.*.other_item_id' => 'required_with:other_items|exists:other_items,id',
            'other_items.*.quantity' => 'required_with:other_items|numeric|min:0',
            'other_items.*.price' => 'nullable|numeric|min:0',
            'other_items.*.price_includes_tax' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $request->receiver_store_id;

        // Cart fallback: if items not provided, load from user's cart
        $fromCart = false;
        $cart = null;
        $requestItems = $request->items;

        if (empty($requestItems) && $storeId) {
            $cart = Cart::where('user_id', $user->id)
                ->where('receiver_store_id', $storeId)
                ->with('items')
                ->first();

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'Cart is empty. Add items before placing an order.'], 422);
            }

            $requestItems = $cart->items->map(function ($ci) {
                return [
                    'product_id' => $ci->product_id,
                    'unit_id' => $ci->unit_id,
                    'quantity' => (float) $ci->quantity,
                ];
            })->toArray();
            $fromCart = true;
        } elseif (empty($requestItems)) {
            return response()->json(['status' => false, 'message' => 'Items are required.'], 422);
        }

        // Load other items from cart if placing from cart
        if ($fromCart && $cart && !$request->has('other_items')) {
            $cartOtherItems = $cart->otherItems;
            if ($cartOtherItems->isNotEmpty()) {
                $request->merge([
                    'other_items' => $cartOtherItems->map(function ($coi) {
                        return [
                            'other_item_id' => $coi->other_item_id,
                            'quantity' => (float) $coi->quantity,
                            'price' => $coi->price,
                            'price_includes_tax' => (int) $coi->price_includes_tax,
                        ];
                    })->toArray()
                ]);
            }
        }

        $processedItems = [];
        $subtotal = 0;
        $subtotal2 = 0;

        $recSto = Store::where('id', request('receiver_store_id'))->value('pricing_tier_id') ?? null;

        foreach ($requestItems as $item) {
            $price = $this->getPriceByUnit($item['product_id'], $item['unit_id'], [$request->receiver_store_id, $recSto], $item['quantity']);
            $itemSubtotal = $price['price'] * $item['quantity'];
            
            $subtotal2 += $price['price'] * $item['quantity'];
            $subtotal += $price['ge_price'] * $item['quantity'];

            $tProduct = OrderProduct::find($item['product_id']);
            $tSlab = \App\Models\TaxSlab::find($tProduct->tax_slab_id ?? null);

            $processedItems[] = [
                'product_id' => $item['product_id'],
                'unit_id' => $item['unit_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $price['price'],
                'subtotal' => $itemSubtotal,
                'ge_price' => $price['ge_price'],
                'gi_price' => $price['gi_price'],
                'tax_slab_id' => $tProduct->tax_slab_id ?? null,
                'cgst' => $tSlab->cgst ?? 0,
                'sgst' => $tSlab->sgst ?? 0
            ];
        }

        $setting = \App\Models\Setting::first();
        $cgstPercent = $setting->cgst_percentage ?? 0;
        $sgstPercent = $setting->sgst_percentage ?? 0;
        $taxPercent = $cgstPercent + $sgstPercent;

        $cgstAmount = round($subtotal2 * $cgstPercent / 100, 2);
        $sgstAmount = round($subtotal2 * $sgstPercent / 100, 2);

        // Process Other Items
        $processedOtherItems = [];
        if ($request->has('other_items') && is_array($request->other_items)) {
            foreach ($request->other_items as $oi) {
                if (empty($oi['other_item_id'])) continue;
                $otherItem = \App\Models\OtherItem::find($oi['other_item_id']);
                if (!$otherItem) continue;

                $priceIncludesTax = isset($oi['price_includes_tax']) ? (int) $oi['price_includes_tax'] : (int) ($otherItem->price_includes_tax ?? 0);

                $reqPrice = isset($oi['price']) ? floatval($oi['price']) : 0;
                $unitPrice = $reqPrice > 0 ? $reqPrice : floatval($otherItem->price_per_piece ?? 0);

                if ($priceIncludesTax == 0 && isset($otherItem->taxSlab->id)) {
                    $txAmt = (($unitPrice * ($otherItem->taxSlab->cgst ?? 0)) / 100) * 2;
                    $unitPrice += $txAmt;
                }

                $qty = floatval($oi['quantity']);
                $total = $unitPrice * $qty;
                $pricingType = $otherItem->pricing_type ?? 'fixed';

                $subtotal += $total;
                $subtotal2 += $total;

                $processedOtherItems[] = [
                    'other_item_id' => $oi['other_item_id'],
                    'quantity' => $qty,
                    'price' => $unitPrice,
                    'total' => $total,
                    'pricing_type' => $pricingType,
                    'tax_slab_id' => $otherItem->tax_slab_id,
                    'price_includes_tax' => $priceIncludesTax,
                ];
            }
        }

        // Additional charges (optional)
        $additionalChargesTotal = 0;
        if (is_array($request->additional_charges)) {
            foreach ($request->additional_charges as $charge) {
                if (!empty($charge['title']) && isset($charge['amount'])) {
                    $additionalChargesTotal += floatval($charge['amount']);
                }
            }
        }

        $taxableBase = $subtotal + $additionalChargesTotal;
        $discountAmount = 0;
        $grandTotal = $taxableBase + $cgstAmount + $sgstAmount - $discountAmount;

        // Time slot mapping

        $deliveryDate = Carbon::parse($request->delivery_date);

        $slot = [
            'start' => $deliveryDate->format('H:i'),
            'end' => $deliveryDate->addMinutes(30)->format('H:i')
        ];

        DB::beginTransaction();
        try {
            $storeIdRe = \App\Models\UserStore::where('user_id', $request->dealer_id)->value('store_id');

            // Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'order_type' => $request->order_type,
                'sender_store_id' => $request->sender_store_id,
                'dealer_id' => $request->dealer_id,
                'receiver_store_id' => isset($storeId) ? $storeId : $storeIdRe,
                'for_customer' => $request->for_customer ?? false,
                'customer_first_name' => $request->customer_first_name,
                'customer_second_name' => $request->customer_second_name,
                'alternate_name' => $request->alternate_name,
                'alternate_phone_number' => $request->alternate_phone_number,
                'customer_email' => $request->customer_email,
                'bill_to_same_as_ship_to' => $request->bill_to_same_as_ship_to == 1 ? 1 : 0,
                'customer_phone_number' => $request->customer_phone_number,
                'bill_to_type' => 'factory',
                'bill_to_id' => $storeId,
                'status' => Order::STATUS_PENDING,
                'collect_on_delivery' => $request->collect_on_delivery ?? false,
                'total_amount' => $subtotal,
                'tax_type' => 0, // Percentage
                'tax_amount' => $cgstAmount + $sgstAmount, // This seems to be 0 initialized above and not updated? Fixed below to be sum of GST
                'cgst_percentage' => $cgstPercent,
                'sgst_percentage' => $sgstPercent,
                'cgst_amount' => $cgstAmount,
                'sgst_amount' => $sgstAmount,
                'discunt_type' => 1, // Fixed
                'discount_amount' => $discountAmount,
                'net_amount' => $grandTotal,
                'delivery_schedule_from' => $deliveryDate->copy()->setTimeFromTimeString($slot['start']),
                'delivery_schedule_to' => $deliveryDate->copy()->setTimeFromTimeString($slot['end']),
                'handling_instructions' => $request->handling_instructions,
                'handling_note' => $request->handling_note,
                'remarks' => $request->remarks,
                'created_by' => $user->id,
                'billing_name' => $request->billing_name,
                'billing_contact_number' => $request->billing_contact_number,
                'billing_email' => $request->billing_email,
                'billing_address_1' => $request->billing_address_1,
                'billing_address_2' => $request->billing_address_2,
                'billing_pincode' => $request->billing_pincode,
                'customer_remark' => $request->delivery_remark,
                'billing_gst_in' => $request->billing_gst_in,
                'shipping_name' => $request->shipping_name,
                'shipping_contact_number' => $request->shipping_contact_number,
                'shipping_email' => $request->shipping_email,
                'shipping_address_1' => $request->shipping_address_1,
                'delivery_address' => $request->shipping_address_1,
                'delivery_link' => $request->shipping_google_map_link,
                'shipping_address_2' => $request->shipping_address_2,
                'shipping_pincode' => $request->shipping_pincode,
                'shipping_gst_in' => $request->shipping_gst_in,
                'billing_latitude' => $request->billing_latitude,
                'billing_longitude' => $request->billing_longitude,
                'billing_google_map_link' => $request->billing_google_map_link,
                'shipping_latitude' => $request->shipping_latitude,
                'shipping_longitude' => $request->shipping_longitude,
                'shipping_google_map_link' => $request->shipping_google_map_link,
                'vehicle_id' => $request->vehicle_id,
                'delivery_to_store' => $request->delivery_to_store == 1 ? 1 : 0
            ]);

            // Create order items
            foreach ($processedItems as $pItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $pItem['product_id'],
                    'unit_id' => $pItem['unit_id'],
                    'quantity' => $pItem['quantity'],
                    'unit_price' => $pItem['unit_price'],
                    'tax_percent' => $taxPercent,
                    'subtotal' => $pItem['subtotal'],
                    'ge_price' => $pItem['ge_price'],
                    'gi_price' => $pItem['gi_price'],
                    'tax_slab_id' => $pItem['tax_slab_id'],
                    'cgst' => $pItem['cgst'],
                    'sgst' => $pItem['sgst']
                ]);
            }

            // Create Order Other Items
            foreach ($processedOtherItems as $poi) {
                \App\Models\OrderOtherItem::create([
                    'order_id' => $order->id,
                    'other_item_id' => $poi['other_item_id'],
                    'quantity' => $poi['quantity'],
                    'unit_price' => $poi['price'],
                    'subtotal' => $poi['total'],
                    'pricing_type' => $poi['pricing_type'],
                    'price_includes_tax' => $poi['price_includes_tax'],
                ]);
            }

            // Persist additional charges if provided
            if (is_array($request->additional_charges)) {
                foreach ($request->additional_charges as $charge) {
                    if (!empty($charge['title']) && isset($charge['amount']) && floatval($charge['amount']) > 0) {
                        OrderCharge::create([
                            'order_id' => $order->id,
                            'title' => $charge['title'],
                            'amount' => floatval($charge['amount']),
                        ]);
                    }
                }
            }

            // Clear cart after successful order placement
            if ($fromCart && $cart) {
                $cart->items()->delete();
                $cart->otherItems()->delete();
                $cart->delete();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $this->getStatusText($order->status),
                    'status_id' => $this->getStatusId($order->status),
                    'grand_total' => $grandTotal,
                    'additional_charges_total' => $additionalChargesTotal
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to place order: ' . $e->getMessage()], 500);
        }
    }

    public function getOrders(Request $request)
    {
        $status = $request->input('status');
        $perPage = $request->input('per_page', 15);

        $query = Order::with(['items.product.images', 'vehicle', 'items.unit', 'senderStore', 'receiverStore', 'dealer', 'bill2'])
            ->where(function ($q) {
                if (request('user_type') == 3 || request('user_type') == 4) {
                    $q->whereHas('receiverStore.users', function ($iQ) {
                        $iQ->where('user_id', auth()->user()->id);
                    });
                } else if (request('user_type') == 5) {
                    $q->where('dealer_id', auth()->user()->id);
                } else if (request('user_type') == 6) {
                    $q->where('delivery_user', auth()->user()->id);
                }
            })
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $statusMap = [
                'pending' => Order::STATUS_PENDING,
                'approved' => Order::STATUS_APPROVED,
                'dispatched' => Order::STATUS_DISPATCHED,
                'delivered' => Order::STATUS_DELIVERED,
                'cancelled' => Order::STATUS_CANCELLED
            ];
            if (isset($statusMap[$status])) {
                $query->where('status', $statusMap[$status]);
            }
        }

        $orders = $query->paginate($perPage);

        $data = $orders->getCollection()->map(fn($o) => $this->formatOrderListItem($o));

        return response()->json([
            'status' => true,
            'currency_symbol' => Helper::defaultCurrencySymbol(),
            'data' => $data,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total()
            ]
        ]);
    }

    public function getOrderDetail($id)
    {
        $user = Auth::user();

        $order = Order::with([
            'items.product.images',
            'items.product.category',
            'items.unit',
            'senderStore',
            'receiverStore',
            'deliveryUser',
            'vehicle',
            'createdBy',
            'dealer',
            'bill2',
            'charges',
            'utencils.utencil',
            'utencilHistories.utencil',
            'otherItems.otherItem.taxSlab'
        ])->find($id);

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found'], 404);
        }

        // Status timeline
        $timeline = $this->getOrderTimeline($order);

        // Modification window
        $deliveryDate = Carbon::parse($order->delivery_schedule_from);
        $modifyUntil = $deliveryDate->copy()->subDay()->setTime(10, 0);
        $cancelUntil = $deliveryDate->copy()->subDays(2)->setTime(18, 0);
        $canModify = now()->lt($modifyUntil) && in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_APPROVED]);
        $canCancel = now()->lt($cancelUntil) && in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_APPROVED]);

        $items = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_image' => $item->product->images->first()
                    ? asset('storage/order-product-images/' . $item->product->images->first()->image_path)
                    : null,
                'category' => $item->product->category->name ?? null,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit->name ?? '',
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal
            ];
        });

        $customerSignatureUrl = $order->customer_signature ? asset('storage/order-signatures/' . $order->customer_signature) : null;
        $deliverySignatureUrl = $order->delivery_guy_signature ? asset('storage/order-signatures/' . $order->delivery_guy_signature) : null;
        $payment_proof = $order->payment_proof ? asset('storage/order-signatures/' . $order->payment_proof) : null;

        $qrCode = '';
        $qrLink = '';

        if ($order->bill_to_type == 'store' || $order->bill_to_type == 'factory') {
            $se = Store::find($order->bill_to_id);
            $qrCode = $se->upi_handle ?? '';

            if (isset($se->id)) {
                $tQrLink = 'store_' . $se->id . '_qr.png';
                if (file_exists(storage_path("app/public/qr-codes/{$tQrLink}")) && is_file(storage_path("app/public/qr-codes/{$tQrLink}"))) {
                    $qrLink = asset("storage/qr-codes/{$tQrLink}");
                }
            }
        } else if ($order->bill_to_type == 'user') {
            $ue = User::find($order->bill_to_id);
            $qrCode = $ue->upi_handle ?? '';

            if (isset($ue->id)) {
                $tQrLink = 'user_' . $ue->id . '_qr.png';
                if (file_exists(storage_path("app/public/qr-codes/{$tQrLink}")) && is_file(storage_path("app/public/qr-codes/{$tQrLink}"))) {
                    $qrLink = asset("storage/qr-codes/{$tQrLink}");
                }
            }
        }

        if (!empty($qrCode)) {
            $qrCode = "upi://pay?pa={$qrCode}";
        } else {
            $qrCode = null;
        }

        $charges = $order->charges->map(function ($charge) {
            return [
                'id' => $charge->id,
                'title' => $charge->title,
                'amount' => (float) $charge->amount,
            ];
        });

        // Other Items
        $otherItems = $order->otherItems->map(function ($oi) {
            $srcItem = $oi->otherItem;
            $cgstPercent = $srcItem && $srcItem->taxSlab ? (float)$srcItem->taxSlab->cgst : 0;
            $sgstPercent = $srcItem && $srcItem->taxSlab ? (float)$srcItem->taxSlab->sgst : 0;
            $totalTaxPercent = $cgstPercent + $sgstPercent;

            $uPrice = (float)$oi->unit_price;
            $qty = (float)$oi->quantity;

            // Logic Change: Treat stored price as Tax Inclusive if tax exists
            $basePrice = $uPrice;
            if ($totalTaxPercent > 0) {
                $basePrice = $uPrice / (1 + ($totalTaxPercent / 100));
            }

            $lineTotalBase = $basePrice * $qty;
            $cgstAmt = $lineTotalBase * ($cgstPercent / 100);
            $sgstAmt = $lineTotalBase * ($sgstPercent / 100);

            return [
                'id' => $oi->id,
                'other_item_id' => $oi->other_item_id,
                'name' => $oi->otherItem->name ?? null,
                'quantity' => $qty,
                'price' => $uPrice,
                'total' => (float) $oi->subtotal,
                'pricing_type' => $oi->pricing_type,
                'price_includes_tax' => (int) $oi->price_includes_tax,
                'tax_percentage' => $totalTaxPercent,
                'cgst_percentage' => $cgstPercent,
                'sgst_percentage' => $sgstPercent,
                'tax_amount' => round($cgstAmt + $sgstAmt, 2),
                'cgst_amount' => round($cgstAmt, 2),
                'sgst_amount' => round($sgstAmt, 2),
            ];
        });
        
        $chargesTotal = (float) $order->charges->sum('amount');
        // Utencil summary (sent / received / pending)
        $utencilSummaries = [];
        $sentByUtencil = $order->utencils->groupBy('utencil_id');
        $historyByUtencil = $order->utencilHistories->groupBy('utencil_id');

        foreach ($sentByUtencil as $utencilId => $rows) {
            $sentQty = $rows->sum('quantity');
            $receivedQty = 0;
            if (isset($historyByUtencil[$utencilId])) {
                $receivedQty = $historyByUtencil[$utencilId]
                    ->where('type', OrderUtencilHistory::TYPE_RECEIVED)
                    ->sum('quantity');
            }
            $pendingQty = max(0, $sentQty - $receivedQty);

            $utencilSummaries[] = [
                'utencil_id' => $utencilId,
                'utencil_name' => optional($rows->first()->utencil)->name,
                'sent' => (float) $sentQty,
                'received' => (float) $receivedQty,
                'pending' => (float) $pendingQty,
            ];
        }

        // Full utencil transfer history
        $utencilHistory = $order->utencilHistories
            ->sortBy('created_at')
            ->map(function ($h) {
                return [
                    'id' => $h->id,
                    'utencil_id' => $h->utencil_id,
                    'utencil_name' => optional($h->utencil)->name,
                    'quantity' => (float) $h->quantity,
                    'type' => $h->type,
                    'type_label' => $h->type == OrderUtencilHistory::TYPE_SENT ? 'sent' : 'received',
                    'note' => $h->note,
                    'created_at' => $h->created_at ? Carbon::parse($h->created_at)->format('d M, h:i A') : null,
                ];
            })
            ->values();

        return response()->json([
            'status' => true,
            'currency_symbol' => Helper::defaultCurrencySymbol(),
            'data' => [
                'id' => $order->id,
                'qr_code' => $qrCode,
                'qr_code_image_link' => $qrLink,
                'order_number' => $order->order_number,
                'status' => $this->getStatusText($order->status),
                'status_id' => $this->getStatusId($order->status),
                'customer_signature_url' => $customerSignatureUrl,
                'delivery_signature_url' => $deliverySignatureUrl,
                'for_customer' => (int) $order->for_customer,
                'payment_proof' => $payment_proof,
                'customer_first_name' => $order->customer_first_name,
                'customer_phone_number' => $order->customer_phone_number,
                'collect_on_delivery' => $order->collect_on_delivery,
                'alternate_name' => $order->alternate_name,
                'alternate_phone_number' => $order->alternate_phone_number,
                'delivery_remark' => $order->customer_remark,
                'status_code' => $order->status,
                'order_type' => $order->order_type,
                'placed_at' => Carbon::parse($order->created_at)->format('d M, h:i A'),
                'challan_url' => route('orders.download-challan', ['id' => $order->id, 'type' => $order->collect_on_delivery ? 'wp' : 'wop']),
                'invoice_url' => route('orders.download-invoice', $order->id),
                'timeline' => $timeline,
                'items' => $items,
                'items_total' => $items->sum('subtotal'),
                'other_items' => $otherItems,
                'other_items_materials_sgst_amount' => $otherItems->sum('cgst_amount'),
                'other_items_materials_cgst_amount' => $otherItems->sum('sgst_amount'),
                'other_items_materials_total' => $otherItems->sum('total'),
                'delivery_user' => $order->deliveryUser,
                'vehicle' => $order->vehicle->number ?? '',
                'senderStore' => $order->senderStore,
                'receiverStore' => $order->receiverStore,
                'createdBy' => $order->createdBy,
                'bill_to_same_as_ship_to' => $order->bill_to_same_as_ship_to,
                'dealer' => $order->dealer,
                'item_count' => $order->items->count(),
                'total_amount' => (float) $order->total_amount,
                'tax_amount' => $order->tax_type == 0
                    ? round($order->total_amount * $order->tax_amount / 100, 2)
                    : (float) $order->tax_amount,
                'cgst_amount' => (float) $order->cgst_amount,
                'sgst_amount' => (float) $order->sgst_amount,
                'tax_percent' => $order->tax_type == 0 ? $order->tax_amount : null,
                'discount_amount' => (float) $order->discount_amount,
                'net_amount' => ((float) $order->net_amount),
                'additional_charges' => $charges,
                'additional_charges_total' => $chargesTotal,
                'advance_deposit' => (float) $order->amount_collected,
                'due_amount' => (float) ((((float) $order->net_amount)) - $order->amount_collected),
                'delivery_info' => [
                    'expected_date' => Carbon::parse($order->delivery_schedule_from)->format('d M Y'),
                    'time_slot' => $this->getTimeSlotLabel($order->delivery_schedule_from, $order->delivery_schedule_to),
                    'address' => $order->receiverStore ? [
                        'name' => $order->receiverStore->name,
                        'address' => $order->receiverStore->address ?? ''
                    ] : null,
                    'delivery_address' => $order->delivery_address,
                    'delivery_link' => $order->delivery_link,
                    'billing_address' => [
                        'name' => $order->billing_name,
                        'contact_number' => $order->billing_contact_number,
                        'email' => $order->billing_email,
                        'address_1' => $order->billing_address_1,
                        'address_2' => $order->billing_address_2,
                        'pincode' => $order->billing_pincode,
                        'gst_in' => $order->billing_gst_in,
                        'google_map_link' => $order->billing_google_map_link,
                        'latitude' => $order->billing_latitude,
                        'longitude' => $order->billing_longitude,
                    ],
                    'shipping_address' => [
                        'name' => $order->shipping_name,
                        'contact_number' => $order->shipping_contact_number,
                        'email' => $order->shipping_email,
                        'address_1' => $order->shipping_address_1,
                        'address_2' => $order->shipping_address_2,
                        'pincode' => $order->shipping_pincode,
                        'gst_in' => $order->shipping_gst_in,
                        'google_map_link' => $order->shipping_google_map_link,
                        'latitude' => $order->shipping_latitude,
                        'longitude' => $order->shipping_longitude,
                    ],
                    'handling_instructions' => \App\Models\HandlingInstruction::whereIn('id', $order->handling_instructions)->get(),
                    'handling_note' => $order->handling_note
                ],
                'modification_window' => [
                    'can_modify' => $canModify,
                    'can_cancel' => $canCancel,
                    'modify_until' => $modifyUntil->format('d M, h:i A'),
                    'cancel_until' => $cancelUntil->format('d M, h:i A')
                ],
                'remarks' => $order->remarks,
                'utencils_summary' => $utencilSummaries,
                'utencil_history' => $utencilHistory,
            ]
        ]);
    }

    public function updateOrder(Request $request, $id)
    {
        $user = Auth::user();
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found'], 404);
        }

        // Check modification window
        $deliveryDate = Carbon::parse($order->delivery_schedule_from);
        $modifyUntil = $deliveryDate->copy()->subDay()->setTime(10, 0);

        // if (now()->gt($modifyUntil)) {
        //     return response()->json(['status' => false, 'message' => 'Modification window closed'], 400);
        // }

        // if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_APPROVED])) {
        //     return response()->json(['status' => false, 'message' => 'Order cannot be modified'], 400);
        // }

        $validator = Validator::make($request->all(), [
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:order_products,id',
            'items.*.unit_id' => 'required|exists:order_units,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'delivery_date' => 'nullable|date|after_or_equal:today',
            'handling_instructions' => 'nullable|array',
            'handling_note' => 'nullable|string',
            'remarks' => 'nullable|string',
            'billing_name' => 'nullable|string|max:255',
            'billing_contact_number' => 'nullable|string|max:20',
            'billing_email' => 'nullable|email|max:255',
            'billing_address_1' => 'nullable|string|max:500',
            'billing_address_2' => 'nullable|string|max:500',
            'billing_pincode' => 'nullable|string|max:10',
            'billing_gst_in' => 'nullable|string|max:50',
            'shipping_name' => 'nullable|string|max:255',
            'shipping_contact_number' => 'nullable|string|max:20',
            'shipping_email' => 'nullable|email|max:255',
            'shipping_address_1' => 'nullable|string|max:500',
            'shipping_address_2' => 'nullable|string|max:500',
            'shipping_pincode' => 'nullable|string|max:10',
            'shipping_gst_in' => 'nullable|string|max:50',
            'billing_latitude' => 'nullable|numeric',
            'billing_longitude' => 'nullable|numeric',
            'billing_google_map_link' => 'nullable|string',
            'shipping_latitude' => 'nullable|numeric',
            'shipping_longitude' => 'nullable|numeric',
            'shipping_google_map_link' => 'nullable|string',
            'customer_signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'delivery_signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'payment_amount' => 'nullable|numeric|min:0.01',
            'payment_note' => 'nullable|string|max:500',
            'utencils' => 'nullable|array',
            'utencils.*.utencil_id' => 'required_with:utencils|exists:utencils,id',
            'utencils.*.quantity' => 'required_with:utencils|numeric|min:0.01',
            'utencils.*.note' => 'nullable|string|max:255',
            'utencil_returns' => 'nullable|array',
            'utencil_returns.*.utencil_id' => 'required_with:utencil_returns|exists:utencils,id',
            'utencil_returns.*.quantity' => 'required_with:utencil_returns|numeric|min:0.01',
            'utencil_returns.*.note' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            if ($request->has('delivery_date')) {

                $deliveryDate = Carbon::parse($request->delivery_date);

                $slot = [
                    'start' => $deliveryDate->format('H:i'),
                    'end' => $deliveryDate->addMinutes(30)->format('H:i')
                ];

                $newDate = Carbon::parse($request->delivery_date);
                $order->delivery_schedule_from = $newDate->copy()->setTimeFromTimeString($slot['start']);
                $order->delivery_schedule_to = $newDate->copy()->setTimeFromTimeString($slot['end']);
            }

            if ($request->has('handling_instructions')) {
                $order->handling_instructions = $request->handling_instructions;
            }
            if ($request->has('handling_note')) {
                $order->handling_note = $request->handling_note;
            }

            if ($request->has('utencils_collected')) {
                $order->utencils_collected = $request->utencils_collected;
            }

            if ($request->has('payment_received')) {
                $order->payment_received = $request->payment_received;
                $order->amount_collected = (float) $request->amount_collected;

                $log = OrderPaymentLog::create([
                    'order_id' => $order->id,
                    'received_by_user_id' => Auth::id(),
                    'type' => 0, // Add
                    'amount' => floatval($request->amount_collected ?? 0),
                    'text' => 'Initial payment collected on creation.',
                ]);

                if ($order->receiver_store_id) {
                    (new \App\Services\LedgerService)->createCredit(
                        $order->receiver_store_id,
                        $log->amount,
                        now()->format('Y-m-d'),
                        'order_payment_log',
                        $log->id,
                        null,
                        'Initial Payment on Order #' . $order->order_number,
                        $order->order_number
                    );
                }
            }

            if ($request->has('payment_method')) {
                $order->payment_method = $request->payment_method;
            }

            if ($request->has('remarks')) {
                $order->remarks = $request->remarks;
            }

            if ($request->has('status') && $request->status == 2) {
                $order->status = Order::STATUS_DISPATCHED;
                $order->dispatched_at = now();
            }

            if ($request->has('status') && $request->status == 3) {
                $order->status = Order::STATUS_DELIVERED;
                $order->delivered_at = now();
            }

            if ($request->has('status') && $request->status == 5) {
                $order->status = Order::STATUS_COMPLETED;
                $order->completed_at = now();
            }

            // Update Address Fields
            $addressFields = [
                'billing_name',
                'billing_contact_number',
                'billing_email',
                'billing_address_1',
                'billing_address_2',
                'billing_pincode',
                'billing_gst_in',
                'shipping_name',
                'shipping_contact_number',
                'shipping_email',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_pincode',
                'shipping_gst_in',
                'dealer_id',
                'billing_latitude',
                'billing_longitude',
                'billing_google_map_link',
                'shipping_latitude',
                'shipping_longitude',
                'shipping_google_map_link',
            ];

            foreach ($addressFields as $field) {
                if ($request->has($field)) {
                    $order->$field = $request->$field;
                }
            }

            // Handle customer signature upload
            if ($request->hasFile('customer_signature')) {
                $signature = $request->file('customer_signature');
                $filename = 'customer_' . $order->id . '_' . time() . '.' . $signature->getClientOriginalExtension();
                $signature->storeAs('public/order-signatures', $filename);
                $order->customer_signature = $filename;
            }

            // Handle delivery guy signature upload
            if ($request->hasFile('delivery_signature')) {
                $signature = $request->file('delivery_signature');
                $filename = 'delivery_' . $order->id . '_' . time() . '.' . $signature->getClientOriginalExtension();
                $signature->storeAs('public/order-signatures', $filename);
                $order->delivery_guy_signature = $filename;
            }

            //Handle payment proof
            if ($request->hasFile('payment_proof')) {
                $signature = $request->file('payment_proof');
                $filename = 'payment_' . $order->id . '_' . time() . '.' . $signature->getClientOriginalExtension();
                $signature->storeAs('public/order-signatures', $filename);
                $order->payment_proof = $filename;
            }

            // Handle payment amount - add to order_payment_logs
            if ($request->has('payment_amount') && $request->payment_amount > 0) {
                OrderPaymentLog::create([
                    'order_id' => $order->id,
                    'received_by_user_id' => $user->id,
                    'type' => 0, // 0 = Add (credited)
                    'text' => $request->payment_note ?? 'Payment received via ' . ($request->payment_method ?? 'Cash'),
                    'amount' => $request->payment_amount,
                ]);

                // Update amount_collected on order
                $order->amount_collected = $order->amount_collected + $request->payment_amount;
            }

            // New utencils sent from API
            if ($request->has('utencils') && is_array($request->utencils)) {
                foreach ($request->utencils as $utencilRow) {
                    if (empty($utencilRow['utencil_id'])) {
                        continue;
                    }

                    $qty = isset($utencilRow['quantity']) ? (float) $utencilRow['quantity'] : 0;
                    if ($qty <= 0) {
                        continue;
                    }

                    $orderUtencil = OrderUtencil::create([
                        'order_id' => $order->id,
                        'utencil_id' => (int) $utencilRow['utencil_id'],
                        'quantity' => $qty,
                        'note' => $utencilRow['note'] ?? null,
                    ]);

                    OrderUtencilHistory::create([
                        'order_id' => $order->id,
                        'utencil_id' => $orderUtencil->utencil_id,
                        'quantity' => $qty,
                        'type' => OrderUtencilHistory::TYPE_SENT,
                        'note' => $orderUtencil->note,
                    ]);
                }
            }

            // Utencils returns (received) from API
            if ($request->has('utencil_returns') && is_array($request->utencil_returns)) {
                // Compute sent and already received for validation
                $sentByUtencil = OrderUtencil::where('order_id', $order->id)
                    ->select('utencil_id', DB::raw('SUM(quantity) as qty'))
                    ->groupBy('utencil_id')
                    ->pluck('qty', 'utencil_id');

                $receivedByUtencil = OrderUtencilHistory::where('order_id', $order->id)
                    ->where('type', OrderUtencilHistory::TYPE_RECEIVED)
                    ->select('utencil_id', DB::raw('SUM(quantity) as qty'))
                    ->groupBy('utencil_id')
                    ->pluck('qty', 'utencil_id');

                foreach ($request->utencil_returns as $returnRow) {
                    if (empty($returnRow['utencil_id'])) {
                        continue;
                    }

                    $utencilId = (int) $returnRow['utencil_id'];
                    $qty = isset($returnRow['quantity']) ? (float) $returnRow['quantity'] : 0;
                    if ($qty <= 0) {
                        continue;
                    }

                    $sent = (float) ($sentByUtencil[$utencilId] ?? 0);
                    $received = (float) ($receivedByUtencil[$utencilId] ?? 0);
                    $pending = max(0.0, $sent - $received);

                    if ($qty > $pending) {
                        DB::rollBack();
                        return response()->json([
                            'status' => false,
                            'message' => 'Cannot receive more utencils than pending for one or more items.',
                        ], 422);
                    }

                    OrderUtencilHistory::create([
                        'order_id' => $order->id,
                        'utencil_id' => $utencilId,
                        'quantity' => $qty,
                        'type' => OrderUtencilHistory::TYPE_RECEIVED,
                        'note' => $returnRow['note'] ?? null,
                    ]);
                }
            }            

            $order->save();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to update order: ' . $e->getMessage()], 500);
        }
    }

    public function utencils(Request $request) {
        return response()->json(['status' => true, 'data' => Utencil::all()]);
    }

    public function getPriceByUnit($productId, $unitId, $stArrr = [], $quantity = null)
    {
        $stId = isset($stArrr[0]) ? $stArrr[0] : null;
        $ptId = isset($stArrr[1]) ? $stArrr[1] : null;

        if ($quantity !== null && $quantity < 1) {
            $quantity = 1;
        }

        $settings = \App\Models\Setting::select('id', 'cgst_percentage', 'sgst_percentage', 'company_store_discount')->first();
        $prod = OrderProduct::find($productId);
        $finalGst = (float)($prod->taxSlab->cgst ?? 0) + (float)($prod->taxSlab->sgst ?? 0);
        $gstInclusivePrice = $gstExclusivePrice = 0;
        $isCompanyStore = false;

        $override = UnitPriceTier::where('product_id', $productId)
            ->where('product_unit_id', $unitId)
            ->where('pricing_tier_id', $ptId)
            ->first();

        if (Store::where('id', $stId)->whereHas('storetype', function ($builder) {
            $builder->where('name', 'store');
        })->whereHas('modeltype', function ($builder) {
            $builder->whereIn('name', ['company']);
        })->exists()) {
            $isCompanyStore = true;
        }

        if ($override) {
            $gstInclusivePrice = $override->amount ?? 0;

            if ($quantity !== null && $quantity >= 1) {

                $discountRow = UnitDiscountTier::query()
                    ->where('pricing_tier_id', $ptId)
                    ->where('product_id', $productId)
                    ->where('product_unit_id', $unitId)
                    ->where('min_qty', '<=', $quantity)
                    ->where(function ($q) use ($quantity) {
                        $q->whereNull('max_qty')
                            ->orWhere('max_qty', '>=', $quantity);
                    })
                    ->orderBy('min_qty', 'desc')
                    ->first();

                if ($discountRow) {
                    $basePrice = (float) $gstInclusivePrice;
                    $discountAmount = 0.0;

                    if ((int) $discountRow->discount_type === UnitDiscountTier::TYPE_PERCENTAGE) {
                        $discountAmount = $basePrice * ((float) $discountRow->discount_amount / 100);
                    } else {
                        $discountAmount = (float) $discountRow->discount_amount;
                    }

                    $discountedPrice = max(0.0, $basePrice - $discountAmount);

                    if ($isCompanyStore && isset($settings->company_store_discount) && is_numeric($settings->company_store_discount) && $settings->company_store_discount > 0) {
                        $discountedPrice = $discountedPrice - (($discountedPrice * $settings->company_store_discount) / 100);
                    }
                    
                    $gstInclusivePrice = $discountedPrice;
                    $gstExclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $finalGst) / 100);

                    return [
                        'gi_price' => $gstInclusivePrice, 
                        'ge_price' => $gstExclusivePrice, 
                        'price' => $gstInclusivePrice
                    ];
                } else {
                    if ($isCompanyStore && isset($settings->company_store_discount) && is_numeric($settings->company_store_discount) && $settings->company_store_discount > 0) {
                        $gstInclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $settings->company_store_discount) / 100);
                    }

                    $gstExclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $finalGst) / 100);

                    return [
                        'gi_price' => $gstInclusivePrice, 
                        'ge_price' => $gstExclusivePrice, 
                        'price' => $gstInclusivePrice
                    ];
                }
            }
        } else {
            $productUnit = OrderProductUnit::where('order_product_id', $productId)
                ->where('unit_id', $unitId)
                ->first();

            if ($isCompanyStore && isset($settings->company_store_discount) && is_numeric($settings->company_store_discount) && $settings->company_store_discount > 0) {
                $gstInclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $settings->company_store_discount) / 100);
            }

            $gstInclusivePrice = ($productUnit ? $productUnit->price : 0);
            $gstExclusivePrice = $gstInclusivePrice - (($gstInclusivePrice * $finalGst) / 100);
        }

        return [
            'gi_price' => $gstInclusivePrice, 
            'ge_price' => isset($gstExclusivePrice) ? $gstExclusivePrice : 0, 
            'price' => $gstInclusivePrice
        ];
    }

    private function doNotCalculateDiscount($productId, $unitId, $ptId = null, $quantity = null)
    {
        $override = UnitPriceTier::where('pricing_tier_id', $ptId)
            ->where('product_id', $productId)
            ->where('product_unit_id', $unitId)
            ->first();

        if ($override) {
            $gstExclusivePrice = $override->amount ?? 0;
        } else {
            $productUnit = OrderProductUnit::where('order_product_id', $productId)
                ->where('unit_id', $unitId)
                ->first();

            $gstExclusivePrice = ($productUnit ? $productUnit->price : 0);
        }

        return $gstExclusivePrice;
    }    

    private function formatOrderSummary($orders)
    {
        $all = [];

        foreach ($orders as $order) {

            $qrCode = '';

            if ($order->bill_to_type == 'store' || $order->bill_to_type == 'factory') {
                $qrCode = Store::find($order->bill_to_id)->upi_handle ?? '';
            } else if ($order->bill_to_type == 'user') {
                $qrCode = User::find($order->bill_to_id)->upi_handle ?? '';
            }

            if (!empty($qrCode)) {
                $qrCode = "upi://pay?pa={$qrCode}";
            } else {
                $qrCode = null;
            }

            $all[] = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $this->getStatusText($order->status),
                'status_id' => $this->getStatusId($order->status),
                'qr_code' => $qrCode,
                'status_code' => $order->status,
                'item_count' => $order->items->count(),
                'net_amount' => ((float) $order->net_amount),
                'delivery_user' => $order->deliveryUser,
                'vehicle' => $order->vehicle->number ?? '',
                'delivery_time' => $order->delivery_schedule_from
                    ? Carbon::parse($order->delivery_schedule_from)->format('g:i A') . ' - ' . Carbon::parse($order->delivery_schedule_to)->format('g:i A')
                    : null,
                'challan_url' => route('orders.download-challan', $order->id),
                'invoice_url' => route('orders.download-invoice', $order->id),
                'items_preview' => $order->items->take(3)->map(fn($i) => [
                    'name' => $i->product->name ?? 'Unknown',
                    'quantity' => $i->quantity,
                    'unit' => $i->unit->name ?? ''
                ])
            ];
        }

        return $all;
    }

    private function formatOrderListItem($order)
    {
        $qrCode = '';

        if ($order->bill_to_type == 'store' || $order->bill_to_type == 'factory') {
            $qrCode = Store::find($order->bill_to_id)->upi_handle ?? '';
        } else if ($order->bill_to_type == 'user') {
            $qrCode = User::find($order->bill_to_id)->upi_handle ?? '';
        }

        if (!empty($qrCode)) {
            $qrCode = "upi://pay?pa={$qrCode}";
        } else {
            $qrCode = null;
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_type' => $order->order_type,
            'status' => $this->getStatusText($order->status),
            'status_id' => $this->getStatusId($order->status),
            'status_code' => $order->status,
            'item_count' => $order->items->count(),
            'total_amount' => (float) $order->total_amount,
            'tax_amount' => (float) $order->tax_amount,
            'cgst_amount' => (float) $order->cgst_amount,
            'sgst_amount' => (float) $order->sgst_amount,
            'net_amount' => (float) $order->net_amount,
            'advance_deposit' => (float) $order->amount_collected,
            'due_amount' => (float) ($order->net_amount - $order->amount_collected),
            'delivery_user' => $order->deliveryUser,
            'senderStore' => $order->senderStore,
            'vehicle' => $order->vehicle->number ?? '',
            'qr_code' => $qrCode,
            'receiverStore' => $order->receiverStore,
            'dealer' => $order->dealer,
            'placed_at' => Carbon::parse($order->created_at)->format('d M, h:i A'),
            'delivery_date' => $order->delivery_schedule_from
                ? Carbon::parse($order->delivery_schedule_from)->format('d M Y')
                : null,
            'challan_url' => route('orders.download-challan', $order->id),
            'invoice_url' => route('orders.download-invoice', $order->id),
        ];
    }

    private function getStatusText($status)
    {
        return [
            Order::STATUS_PENDING => 'Pending',
            Order::STATUS_APPROVED => 'Approved',
            Order::STATUS_DISPATCHED => 'Out for Delivery',
            Order::STATUS_DELIVERED => 'Delivered',
            Order::STATUS_CANCELLED => 'Cancelled',
            Order::STATUS_COMPLETED => 'Completed'
        ][$status] ?? 'Unknown';
    }

    private function getStatusId($status)
    {
        return [
            Order::STATUS_PENDING => 0,
            Order::STATUS_APPROVED => 1,
            Order::STATUS_DISPATCHED => 2,
            Order::STATUS_DELIVERED => 3,
            Order::STATUS_CANCELLED => 4,
            Order::STATUS_COMPLETED => 5
        ][$status] ?? 0;
    }

    private function getOrderTimeline($order)
    {
        $timeline = [];

        $timeline[] = [
            'step' => 'Order Placed',
            'status' => 'completed',
            'date' => Carbon::parse($order->created_at)->format('d M, h:i A')
        ];

        if ($order->status >= Order::STATUS_APPROVED) {
            $timeline[] = [
                'step' => 'Approved',
                'status' => 'completed',
                'description' => 'Factory accepted. Preparing items.',
                'date' => $order->approved_at ? Carbon::parse($order->approved_at)->format('d M, h:i A') : null
            ];
        } else {
            $timeline[] = [
                'step' => 'Approved',
                'status' => $order->status == Order::STATUS_CANCELLED ? 'cancelled' : 'pending',
                'description' => 'Awaiting approval'
            ];
        }

        if ($order->status >= Order::STATUS_DISPATCHED) {
            $timeline[] = [
                'step' => 'Dispatched',
                'status' => 'completed',
                'date' => $order->dispatched_at ? Carbon::parse($order->dispatched_at)->format('d M, h:i A') : null
            ];
        } else {
            $timeline[] = [
                'step' => 'Dispatched',
                'status' => in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_APPROVED]) ? 'pending' : ($order->status == Order::STATUS_CANCELLED ? 'cancelled' : 'pending'),
                'description' => $order->delivery_schedule_from ? 'Estimated: ' . Carbon::parse($order->delivery_schedule_from)->format('d M, h:i A') : null
            ];
        }

        if ($order->status >= Order::STATUS_DELIVERED) {
            $timeline[] = [
                'step' => 'Delivered',
                'status' => 'completed',
                'date' => $order->delivered_at ? Carbon::parse($order->delivered_at)->format('d M, h:i A') : null
            ];
        } else {
            $timeline[] = [
                'step' => 'Delivered',
                'status' => $order->status == Order::STATUS_CANCELLED ? 'cancelled' : 'pending'
            ];
        }

        return $timeline;
    }

    private function getTimeSlotLabel($from, $to)
    {
        if (!$from || !$to) return null;

        return Carbon::parse($from)->format('H:i A') . ' - ' . Carbon::parse($to)->format('H:i A');
    }

    public function handlingInstructions(Request $request) {
        return response()->json([
            'status' => true,
            'data' => \App\Models\HandlingInstruction::all()
        ]);
    }

    public function otherItems(Request $request) {
        $whichGst = 'cs';

        return response()->json([
            'status' => true,
            'data' => \App\Models\OtherItem::with('taxSlab')->get()->map(function ($row) use($whichGst) {

                if ($row->pricing_type == 'fixed') {
                    if ($row->price_includes_tax) {

                        $row->cgst_price = ($row->price_per_piece * ($row->taxSlab->cgst ?? 0)) / 100;
                        $row->sgst_price = ($row->price_per_piece * ($row->taxSlab->sgst ?? 0)) / 100;
                        $row->igst_price = ($row->price_per_piece * ($row->taxSlab->igst ?? 0)) / 100;
                        
                        if ($whichGst == 'cs') {
                            $row->price = $row->price_per_piece - ($row->cgst_price + $row->sgst_price);
                        } else {
                            $row->price = $row->price_per_piece - ($row->igst_price);
                        }

                        $row->final_price = $row->price_per_piece;
                    } else {
                        $row->price = $row->price_per_piece;
                        $row->cgst_price = ($row->price_per_piece * ($row->taxSlab->cgst ?? 0)) / 100;
                        $row->sgst_price = ($row->price_per_piece * ($row->taxSlab->sgst ?? 0)) / 100;
                        $row->igst_price = ($row->price_per_piece * ($row->taxSlab->igst ?? 0)) / 100;

                        if ($whichGst == 'cs') {
                            $row->final_price = $row->price_per_piece + ($row->cgst_price + $row->sgst_price);
                        } else {
                            $row->final_price = $row->price_per_piece + ($row->igst_price);
                        }
                    }
                } else {
                    $row->price = 0;
                    $row->cgst_price = 0;
                    $row->sgst_price = 0;
                    $row->igst_price = 0;
                    $row->final_price = 0;
                }

                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'pricing_type' => $row->pricing_type,
                    'tax_slab' => $row->taxSlab,
                    'price' => $row->price,
                    'cgst_price' => $row->cgst_price,
                    'sgst_price' => $row->sgst_price,
                    'igst_price' => $row->igst_price,
                    'final_price' => $row->final_price
                ];
            })
        ]);
    }

    public function settings(Request $request) {
        return response()->json([
            'status' => true,
            'data' => \App\Models\Setting::with('defaultCurrency')->get()->map(function ($el) {
                $el->qr_image_link = is_file(public_path('storage/qr-codes/company_upi_qr.png')) ? asset('storage/qr-codes/company_upi_qr.png') : null;
                $el->which_tax = $this->hasStoreWithGstPrefix24() ? 'cs' : 'i';
                return $el;
            })[0]
        ]);
    }

    public function hasStoreWithGstPrefix24()
    {
        if (Store::whereHas('users', function ($builder) {
            $builder->where('user_id', auth()->user()->id);
        })->doesntExist()) {
            return true;
        }

        return Store::whereHas('users', function ($builder) {
            $builder->where('user_id', auth()->user()->id);
        })->where(function ($query) {
            $query->whereNotNull('gst_in')
                ->whereRaw("LEFT(LTRIM(gst_in), 2) = 24");
        })->exists();
    }

    public function reorder(Request $request, $order_id)
    {
        $order = Order::with([
            'items',
            'otherItems',
            'charges'
        ])->find($order_id);

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found'], 404);
        }

        $user = Auth::user();
        $storeId = $order->receiver_store_id;
        $dealerId = $order->dealer_id;

        // Ensure the request has the correct store/dealer ID for getCart()
        $request->merge([
            'receiver_store_id' => $storeId,
            'dealer_id' => $dealerId,
        ]);

        $cartQuery = Cart::where('user_id', $user->id);
        if ($storeId) {
            $cartQuery->where('receiver_store_id', $storeId);
        } elseif ($dealerId) {
            $cartQuery->where('dealer_id', $dealerId);
        }

        $cart = $cartQuery->first();
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'receiver_store_id' => $storeId,
                'dealer_id' => $dealerId,
            ]);
        }

        // Add Products to Cart
        foreach ($order->items as $item) {
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $item->product_id)
                ->where('unit_id', $item->unit_id)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += (float) $item->quantity;
                $existingItem->save();
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'quantity' => (float) $item->quantity,
                ]);
            }
        }

        // Add Other Items to Cart
        foreach ($order->otherItems as $oi) {
            $masterOI = \App\Models\OtherItem::find($oi->other_item_id);
            if ($masterOI) {
                $existingOItem = CartOtherItem::where('cart_id', $cart->id)
                    ->where('other_item_id', $masterOI->id)
                    ->first();

                if ($existingOItem) {
                    $existingOItem->quantity += (float) $oi->quantity;
                    $existingOItem->save();
                } else {
                    CartOtherItem::create([
                        'cart_id' => $cart->id,
                        'other_item_id' => $masterOI->id,
                        'quantity' => (float) $oi->quantity,
                        'price' => (float) $masterOI->price_per_piece,
                        'price_includes_tax' => (int) $masterOI->price_includes_tax,
                    ]);
                }
            }
        }

        // Optional: Get full cart payload
        $cartResponse = $this->getCart($request)->getData(true);
        $cartResponse['old_order_data'] = $order;

        return response()->json($cartResponse);
    }
    // ─── Cart API Methods ────────────────────────────────────────────────

    /**
     * Get the user's cart for a given store with live-computed prices and per-product tax data.
     */
    public function getCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'required_without:dealer_id|exists:stores,id|nullable',
            'dealer_id' => 'required_without:receiver_store_id|exists:users,id|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $request->receiver_store_id;
        $dealerId = $request->dealer_id;
        $recSto = $storeId ? (Store::where('id', $storeId)->value('pricing_tier_id') ?? null) : null;

        $query = Cart::where('user_id', $user->id);
        if ($storeId) {
            $query->where('receiver_store_id', $storeId);
        } elseif ($dealerId) {
            $query->where('dealer_id', $dealerId);
        }

        $cart = $query->with([
                'items.product.images',
                'items.product.category',
                'items.product.taxSlab',
                'items.unit',
                'otherItems.otherItem.taxSlab'
            ])
            ->first();

        $cartEmpty = !$cart || ($cart->items->isEmpty() && $cart->otherItems->isEmpty());

        if ($cartEmpty) {
            return response()->json([
                'status' => true,
                'currency_symbol' => Helper::defaultCurrencySymbol(),
                'data' => [
                    'items' => [],
                    'other_items' => [],
                    'item_count' => 0,
                    'other_item_count' => 0,
                ]
            ]);
        }

        // Products with per-product tax slab
        $items = $cart->items->map(function ($ci) use ($storeId, $recSto) {
            $priceData = $this->getPriceByUnit($ci->product_id, $ci->unit_id, [$storeId, $recSto], $ci->quantity);
            $product = $ci->product;
            $taxSlab = $product ? $product->taxSlab : null;

            $cgstPercent = $taxSlab ? (float) $taxSlab->cgst : 0;
            $sgstPercent = $taxSlab ? (float) $taxSlab->sgst : 0;

            $gePrice = (float) ($priceData['ge_price'] ?? 0);
            $giPrice = (float) ($priceData['gi_price'] ?? 0);
            $lineBase = $gePrice * $ci->quantity;
            $lineBase2 = $giPrice * $ci->quantity;
            $lineCgst = round($lineBase2 * $cgstPercent / 100, 2);
            $lineSgst = round($lineBase2 * $sgstPercent / 100, 2);

            return [
                'product_id' => $ci->product_id,
                'product_name' => $product->name ?? 'N/A',
                'product_image' => $product && $product->images->first()
                    ? asset('storage/order-product-images/' . $product->images->first()->image_path)
                    : null,
                'category' => $product->category->name ?? null,
                'unit_id' => $ci->unit_id,
                'unit_name' => $ci->unit->name ?? '',
                'quantity' => (float) $ci->quantity,
                'unit_price' => (float) ($priceData['price'] ?? 0),
                'ge_price' => $gePrice,
                'gi_price' => (float) ($priceData['gi_price'] ?? 0),
                'subtotal' => (float) (($priceData['ge_price'] ?? 0) * $ci->quantity),
                'tax_slab_id' => $product->tax_slab_id,
                'tax_slab_name' => $taxSlab->name ?? null,
                'cgst_percent' => $cgstPercent,
                'sgst_percent' => $sgstPercent,
                'cgst_amount' => $lineCgst,
                'sgst_amount' => $lineSgst,
                'line_total' => round($lineBase + $lineCgst + $lineSgst, 2),
            ];
        });
        
        $groupedItems = $items
        ->groupBy('tax_slab_id')
        ->map(function ($group) {
            return [
                'tax_slab_id'   => $group->first()['tax_slab_id'],
                'tax_slab_name' => $group->first()['tax_slab_name'], // group name
                'products'      => $group->values(),
                'total_quantity'=> $group->sum('quantity'),
                'total_cgst'    => $group->sum('cgst_amount'),
                'total_sgst'    => $group->sum('sgst_amount'),
                'total_amount'  => $group->sum('line_total'),
            ];
        })
        ->values();

        // Other items with per-item tax slab
        $otherItems = $cart->otherItems->map(function ($coi) {
            $srcItem = $coi->otherItem;
            if (!$srcItem) return null;

            $taxSlab = $srcItem->taxSlab;
            $cgstPercent = $taxSlab ? (float) $taxSlab->cgst : 0;
            $sgstPercent = $taxSlab ? (float) $taxSlab->sgst : 0;
            $totalTaxPercent = $cgstPercent + $sgstPercent;

            $unitPrice = $coi->price !== null ? (float) $coi->price : (float) $srcItem->price_per_piece;
            $priceIncludesTax = (int) $coi->price_includes_tax;

            // Compute base price (tax exclusive)
            $basePrice = $unitPrice;
            if ($priceIncludesTax && $totalTaxPercent > 0) {
                $basePrice = $unitPrice / (1 + ($totalTaxPercent / 100));
            }

            $qty = (float) $coi->quantity;
            $lineTotalBase = $basePrice * $qty;
            $cgstAmt = round($lineTotalBase * ($cgstPercent / 100), 2);
            $sgstAmt = round($lineTotalBase * ($sgstPercent / 100), 2);

            return [
                'other_item_id' => $coi->other_item_id,
                'name' => $srcItem->name,
                'pricing_type' => $srcItem->pricing_type,
                'quantity' => $qty,
                'unit_price' => 0,
                'base_price' => 0,
                'price_includes_tax' => $priceIncludesTax,
                'tax_slab_id' => $srcItem->tax_slab_id,
                'tax_slab_name' => $taxSlab->name ?? null,
                'cgst_percent' => $cgstPercent,
                'sgst_percent' => $sgstPercent,
                'cgst_amount' => 0,
                'sgst_amount' => 0,
                'line_total' => 0,
            ];
        })->filter()->values();

        return response()->json([
            'status' => true,
            'currency_symbol' => Helper::defaultCurrencySymbol(),
            'data' => [
                'items' => $groupedItems,
                'other_items' => $otherItems,
                'item_count' => $items->count(),
                'other_item_count' => $otherItems->count(),
            ]
        ]);
    }

    /**
     * Add a product to the cart (upserts — adds quantity if already exists).
     */
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'required_without:dealer_id|exists:stores,id|nullable',
            'dealer_id' => 'required_without:receiver_store_id|exists:users,id|nullable',
            'product_id' => 'required|exists:order_products,id',
            'unit_id' => 'required|exists:order_units,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $request->receiver_store_id;
        $dealerId = $request->dealer_id;

        $cartQuery = Cart::where('user_id', $user->id);
        if ($storeId) $cartQuery->where('receiver_store_id', $storeId);
        elseif ($dealerId) $cartQuery->where('dealer_id', $dealerId);

        $cart = $cartQuery->first();
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'receiver_store_id' => $storeId,
                'dealer_id' => $dealerId,
            ]);
        }

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->where('unit_id', $request->unit_id)
            ->first();

        if ($existingItem) {
            $existingItem->quantity += (float) $request->quantity;
            $existingItem->save();
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'unit_id' => $request->unit_id,
                'quantity' => (float) $request->quantity,
            ]);
        }

        return $this->getCart($request);
    }

    /**
     * Add an other item to the cart.
     */
    public function addOtherItemToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'required_without:dealer_id|exists:stores,id|nullable',
            'dealer_id' => 'required_without:receiver_store_id|exists:users,id|nullable',
            'other_item_id' => 'required|exists:other_items,id',
            'quantity' => 'required|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'price_includes_tax' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $otherItem = \App\Models\OtherItem::find($request->other_item_id);
        $storeId = $request->receiver_store_id;
        $dealerId = $request->dealer_id;

        $cartQuery = Cart::where('user_id', $user->id);
        if ($storeId) $cartQuery->where('receiver_store_id', $storeId);
        elseif ($dealerId) $cartQuery->where('dealer_id', $dealerId);

        $cart = $cartQuery->first();
        if (!$cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'receiver_store_id' => $storeId,
                'dealer_id' => $dealerId,
            ]);
        }

        $existing = CartOtherItem::where('cart_id', $cart->id)
            ->where('other_item_id', $request->other_item_id)
            ->first();

        if ($existing) {
            $existing->quantity += (float) $request->quantity;
            if ($request->has('price')) $existing->price = (float) $request->price;
            $existing->save();
        } else {
            CartOtherItem::create([
                'cart_id' => $cart->id,
                'other_item_id' => $request->other_item_id,
                'quantity' => (float) $request->quantity,
                'price' => $request->has('price') ? (float) $request->price : (float) $otherItem->price_per_piece,
                'price_includes_tax' => $request->has('price_includes_tax') ? (int) $request->price_includes_tax : (int) ($otherItem->price_includes_tax ?? 0),
            ]);
        }

        return $this->getCart($request);
    }

    /**
     * Update (set) the quantity of a specific product+unit in the cart.
     */
    public function updateCartItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'required_without:dealer_id|exists:stores,id|nullable',
            'dealer_id' => 'required_without:receiver_store_id|exists:users,id|nullable',
            'product_id' => 'required|exists:order_products,id',
            'unit_id' => 'required|exists:order_units,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $request->receiver_store_id;
        $dealerId = $request->dealer_id;

        $cartQuery = Cart::where('user_id', $user->id);
        if ($storeId) $cartQuery->where('receiver_store_id', $storeId);
        elseif ($dealerId) $cartQuery->where('dealer_id', $dealerId);

        $cart = $cartQuery->first();

        if (!$cart) {
            return response()->json(['status' => false, 'message' => 'Cart not found.'], 404);
        }

        $item = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->where('unit_id', $request->unit_id)
            ->first();

        if (!$item) {
            return response()->json(['status' => false, 'message' => 'Item not found in cart.'], 404);
        }

        if ((float) $request->quantity <= 0) {
            $item->delete();
        } else {
            $item->quantity = (float) $request->quantity;
            $item->save();
        }

        return $this->getCart($request);
    }

    /**
     * Update (set) the quantity of a specific other item in the cart.
     */
    public function updateCartOtherItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'required_without:dealer_id|exists:stores,id|nullable',
            'dealer_id' => 'required_without:receiver_store_id|exists:users,id|nullable',
            'other_item_id' => 'required|exists:other_items,id',
            'quantity' => 'required|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'price_includes_tax' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $request->receiver_store_id;
        $dealerId = $request->dealer_id;

        $cartQuery = Cart::where('user_id', $user->id);
        if ($storeId) $cartQuery->where('receiver_store_id', $storeId);
        elseif ($dealerId) $cartQuery->where('dealer_id', $dealerId);

        $cart = $cartQuery->first();

        if (!$cart) {
            return response()->json(['status' => false, 'message' => 'Cart not found.'], 404);
        }

        $item = CartOtherItem::where('cart_id', $cart->id)
            ->where('other_item_id', $request->other_item_id)
            ->first();

        if (!$item) {
            return response()->json(['status' => false, 'message' => 'Other item not found in cart.'], 404);
        }

        if ((float) $request->quantity <= 0) {
            $item->delete();
        } else {
            $item->quantity = (float) $request->quantity;
            if ($request->has('price')) $item->price = (float) $request->price;
            if ($request->has('price_includes_tax')) $item->price_includes_tax = (int) $request->price_includes_tax;
            $item->save();
        }

        return $this->getCart($request);
    }

    /**
     * Remove a specific product+unit from the cart.
     */
    public function removeCartItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'required_without:dealer_id|exists:stores,id|nullable',
            'dealer_id' => 'required_without:receiver_store_id|exists:users,id|nullable',
            'product_id' => 'required|exists:order_products,id',
            'unit_id' => 'required|exists:order_units,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $request->receiver_store_id;
        $dealerId = $request->dealer_id;

        $cartQuery = Cart::where('user_id', $user->id);
        if ($storeId) $cartQuery->where('receiver_store_id', $storeId);
        elseif ($dealerId) $cartQuery->where('dealer_id', $dealerId);

        $cart = $cartQuery->first();

        if (!$cart) {
            return response()->json(['status' => false, 'message' => 'Cart not found.'], 404);
        }

        $deleted = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $request->product_id)
            ->where('unit_id', $request->unit_id)
            ->delete();

        if (!$deleted) {
            return response()->json(['status' => false, 'message' => 'Item not found in cart.'], 404);
        }

        return $this->getCart($request);
    }

    /**
     * Remove a specific other item from the cart.
     */
    public function removeCartOtherItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'required_without:dealer_id|exists:stores,id|nullable',
            'dealer_id' => 'required_without:receiver_store_id|exists:users,id|nullable',
            'other_item_id' => 'required|exists:other_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $request->receiver_store_id;
        $dealerId = $request->dealer_id;

        $cartQuery = Cart::where('user_id', $user->id);
        if ($storeId) $cartQuery->where('receiver_store_id', $storeId);
        elseif ($dealerId) $cartQuery->where('dealer_id', $dealerId);

        $cart = $cartQuery->first();

        if (!$cart) {
            return response()->json(['status' => false, 'message' => 'Cart not found.'], 404);
        }

        $deleted = CartOtherItem::where('cart_id', $cart->id)
            ->where('other_item_id', $request->other_item_id)
            ->delete();

        if (!$deleted) {
            return response()->json(['status' => false, 'message' => 'Other item not found in cart.'], 404);
        }

        return $this->getCart($request);
    }

    /**
     * Clear the entire cart for a given store.
     */
    public function clearCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'required_without:dealer_id|exists:stores,id|nullable',
            'dealer_id' => 'required_without:receiver_store_id|exists:users,id|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $request->receiver_store_id;
        $dealerId = $request->dealer_id;

        $cartQuery = Cart::where('user_id', $user->id);
        if ($storeId) $cartQuery->where('receiver_store_id', $storeId);
        elseif ($dealerId) $cartQuery->where('dealer_id', $dealerId);

        $cart = $cartQuery->first();

        if ($cart) {
            $cart->items()->delete();
            $cart->otherItems()->delete();
            $cart->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart cleared successfully.',
        ]);
    }

    /**
     * Get computed summary with per-product tax slab breakdown + other items.
     */
    public function cartSummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_store_id' => 'required_without:dealer_id|exists:stores,id|nullable',
            'dealer_id' => 'required_without:receiver_store_id|exists:users,id|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $storeId = $request->receiver_store_id;
        $dealerId = $request->dealer_id;
        $recSto = $storeId ? (Store::where('id', $storeId)->value('pricing_tier_id') ?? null) : null;

        $cartQuery = Cart::where('user_id', $user->id);
        if ($storeId) $cartQuery->where('receiver_store_id', $storeId);
        elseif ($dealerId) $cartQuery->where('dealer_id', $dealerId);

        $cart = $cartQuery->with(['items.product.taxSlab', 'otherItems.otherItem.taxSlab'])
            ->first();

        if (!$cart || ($cart->items->isEmpty() && $cart->otherItems->isEmpty())) {
            return response()->json([
                'status' => true,
                'currency_symbol' => Helper::defaultCurrencySymbol(),
                'data' => [
                    'item_count' => 0,
                    'other_item_count' => 0,
                    'products_subtotal' => 0,
                    'products_cgst' => 0,
                    'products_sgst' => 0,
                    'products_total' => 0,
                    'other_items_subtotal' => 0,
                    'other_items_cgst' => 0,
                    'other_items_sgst' => 0,
                    'other_items_total' => 0,
                    'grand_total' => 0,
                ]
            ]);
        }

        // Per-product tax calculation
        $productsSubtotal = 0;
        $productsCgst = 0;
        $productsSgst = 0;

        foreach ($cart->items as $ci) {
            $priceData = $this->getPriceByUnit($ci->product_id, $ci->unit_id, [$storeId, $recSto], $ci->quantity);
            $product = OrderProduct::with('taxSlab')->find($ci->product_id);
            $taxSlab = $product ? $product->taxSlab : null;
            $cgst = $taxSlab ? (float) $taxSlab->cgst : 0;
            $sgst = $taxSlab ? (float) $taxSlab->sgst : 0;

            $lineBase = ($priceData['ge_price'] ?? 0) * $ci->quantity;
            $productsSubtotal += $lineBase;
            $productsCgst += round($lineBase * $cgst / 100, 2);
            $productsSgst += round($lineBase * $sgst / 100, 2);
        }
        $productsTotal = round($productsSubtotal + $productsCgst + $productsSgst, 2);

        // Other items tax calculation
        $otherItemsSubtotal = 0;
        $otherItemsCgst = 0;
        $otherItemsSgst = 0;

        foreach ($cart->otherItems as $coi) {
            $srcItem = $coi->otherItem;
            if (!$srcItem) continue;

            $taxSlab = $srcItem->taxSlab;
            $cgst = $taxSlab ? (float) $taxSlab->cgst : 0;
            $sgst = $taxSlab ? (float) $taxSlab->sgst : 0;
            $totalTaxPercent = $cgst + $sgst;

            $unitPrice = $coi->price !== null ? (float) $coi->price : (float) $srcItem->price_per_piece;
            $basePrice = $unitPrice;
            if ((int) $coi->price_includes_tax && $totalTaxPercent > 0) {
                $basePrice = $unitPrice / (1 + ($totalTaxPercent / 100));
            }

            $lineBase = $basePrice * (float) $coi->quantity;
            $otherItemsSubtotal += $lineBase;
            $otherItemsCgst += round($lineBase * $cgst / 100, 2);
            $otherItemsSgst += round($lineBase * $sgst / 100, 2);
        }
        $otherItemsTotal = round($otherItemsSubtotal + $otherItemsCgst + $otherItemsSgst, 2);

        $grandTotal = round($productsTotal + $otherItemsTotal, 2);

        return response()->json([
            'status' => true,
            'currency_symbol' => Helper::defaultCurrencySymbol(),
            'data' => [
                'item_count' => $cart->items->count(),
                'other_item_count' => $cart->otherItems->count(),
                'products_subtotal' => round($productsSubtotal, 2),
                'products_cgst' => round($productsCgst, 2),
                'products_sgst' => round($productsSgst, 2),
                'products_total' => $productsTotal,
                'other_items_subtotal' => round($otherItemsSubtotal, 2),
                'other_items_cgst' => round($otherItemsCgst, 2),
                'other_items_sgst' => round($otherItemsSgst, 2),
                'other_items_total' => $otherItemsTotal,
                'grand_total' => $grandTotal,
            ]
        ]);
    }
}
