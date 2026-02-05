<?php

namespace Modules\Pos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\Color;
use App\Models\Size;
use App\Models\PosOrders;
use App\Models\PosOrdersDetail;
use App\Models\PosPayment;
use App\Models\PosPaymentExpence;
use App\Models\Area;
use App\Models\City;
use App\Models\ColorSize;
use App\Models\Account;
use App\Models\Channel;
use App\Models\ChannelStock;
use App\Models\StockAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PosController extends Controller
{
    public function index()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(8, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        $categories = Category::orderBy('id', 'ASC')->get();
        $selectedChannelId = session('pos_selected_channel_id', null);

        if ($selectedChannelId) {
            $channelStockIds = ChannelStock::where('location_type', 'channel')
                ->where('location_id', $selectedChannelId)
                ->whereNotNull('stock_id')
                ->where('quantity', '>', 0)
                ->distinct()
                ->pluck('stock_id')
                ->toArray();

            if (!empty($channelStockIds)) {
                $stocks = Stock::with(['images', 'category'])
                    ->whereNotNull('category_id')
                    ->whereIn('id', $channelStockIds)
                    ->orderBy('id', 'DESC')
                    ->get();
            } else {
                $stocks = collect([]);
            }
        } else {
            $stocks = Stock::with(['images', 'category', 'colorSizes', 'sizes', 'colors'])
                ->whereNotNull('category_id')
                ->orderBy('id', 'DESC')
                ->get()
                ->filter(function ($stock) {
                    return $this->hasAvailableQuantity($stock);
                });
        }

        $areas = Area::orderBy('area_name_ar', 'ASC')->get(['id', 'area_name_ar', 'area_name_en']);
        $cities = City::orderBy('city_name_ar', 'ASC')
            ->get(['id', 'city_name_ar', 'city_name_en', 'delivery_charges', 'area_id']);

        $selectedChannel = null;
        if ($selectedChannelId) {
            $selectedChannel = Channel::find($selectedChannelId);
        }

        return view('pos::pos_page', compact('categories', 'stocks', 'areas', 'cities', 'selectedChannel'));
    }

    public function getStockDetails($id)
    {
        $stock = Stock::with([
            'colorSizes.size',
            'colorSizes.color',
            'images',
            'category'
        ])->findOrFail($id);

        $selectedChannelId = session('pos_selected_channel_id', null);
        $colorSizes = [];

        if ($selectedChannelId) {
            $channelStocks = ChannelStock::where('location_type', 'channel')
                ->where('location_id', $selectedChannelId)
                ->where('stock_id', $id)
                ->get();

            $groupedStocks = $channelStocks->groupBy(function ($item) {
                return ($item->color_id ?? 'null') . '_' . ($item->size_id ?? 'null');
            });

            foreach ($groupedStocks as $key => $items) {
                $firstItem = $items->first();
                $sizeId = $firstItem->size_id;
                $colorId = $firstItem->color_id;
                $totalQuantity = $items->sum('quantity');

                $sizeName = $firstItem->size_name;
                if (!$sizeName && $sizeId) {
                    $size = Size::find($sizeId);
                    $sizeName = session('locale') === 'ar'
                        ? ($size?->size_name_ar ?? '-')
                        : ($size?->size_name_en ?? '-');
                }

                $colorName = $firstItem->color_name;
                $colorCode = '#000000';
                if ($colorId) {
                    $color = Color::find($colorId);
                    if ($color) {
                        if (!$colorName) {
                            $colorName = session('locale') === 'ar'
                                ? ($color->color_name_ar ?? '-')
                                : ($color->color_name_en ?? '-');
                        }
                        $colorCode = $color->color_code ?? '#000000';
                    }
                }

                $colorSizes[] = [
                    'size_id' => $sizeId,
                    'size_name' => $sizeName ?? '-',
                    'color_id' => $colorId,
                    'color_name' => $colorName ?? '-',
                    'color_code' => $colorCode,
                    'quantity' => $totalQuantity,
                ];
            }
        } else {
            foreach ($stock->colorSizes as $item) {
                $sizeName = session('locale') === 'ar'
                    ? ($item->size?->size_name_ar ?? '-')
                    : ($item->size?->size_name_en ?? '-');

                $colorName = session('locale') === 'ar'
                    ? ($item->color?->color_name_ar ?? '-')
                    : ($item->color?->color_name_en ?? '-');

                $colorSizes[] = [
                    'size_id' => $item->size_id,
                    'size_name' => $sizeName,
                    'color_id' => $item->color_id,
                    'color_name' => $colorName,
                    'color_code' => $item->color?->color_code ?? '#000000',
                    'quantity' => $item->qty ?? 0,
                ];
            }
        }

        return response()->json([
            'id' => $stock->id,
            'name' => session('locale') === 'ar' && $stock->design_name ? $stock->design_name : ($stock->design_name ?: $stock->abaya_code),
            'abaya_code' => $stock->abaya_code,
            'price' => $stock->sales_price ?? 0,
            'image' => $stock->images->first() ? asset($stock->images->first()->image_path) : null,
            'colorSizes' => $colorSizes,
        ]);
    }

    public function searchCustomers(Request $request)
    {
        $search = trim($request->query('search', ''));
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $customers = Customer::with(['city', 'area'])
            ->where(function ($q) use ($search) {
                $q->where('phone', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%");
            })
            ->orderBy('id', 'DESC')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'city_id', 'area_id', 'address']);

        return response()->json($customers);
    }

    public function citiesByArea(Request $request)
    {
        $areaId = $request->query('area_id');
        if (!$areaId) {
            return response()->json([]);
        }

        $areaId = (int)$areaId;

        $cities = City::where('area_id', $areaId)
            ->orderBy('city_name_ar', 'ASC')
            ->get(['id', 'city_name_ar', 'city_name_en', 'delivery_charges']);

        return response()->json($cities);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'payments' => 'required|array|min:1',
            'payments.*.account_id' => 'required|integer',
            'payments.*.amount' => 'required|numeric|min:0.001',
            'totals.subtotal' => 'required|numeric|min:0',
            'totals.total' => 'required|numeric|min:0',
            'customer.name' => 'nullable|string|max:255',
            'customer.phone' => 'nullable|string|max:50',
            'customer.address' => 'nullable|string|max:1000',
            'customer.area' => 'nullable|string|max:255',
            'customer.wilayah' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = Auth::guard('tenant')->user();
            $userId = $user->id ?? null;
            $userName = $user->user_name ?? $user->name ?? 'system';

            $items = $request->input('items', []);
            $payments = $request->input('payments', []);
            $totals = $request->input('totals', []);
            $customerInput = $request->input('customer', []);

            $orderNoInt = (PosOrders::max('order_no') ?? 0) + 1;
            $orderNoFormatted = str_pad($orderNoInt, 6, '0', STR_PAD_LEFT);

            $customerId = null;

            if (!empty($customerInput['phone']) || !empty($customerInput['name'])) {
                $areaId = !empty($customerInput['area']) ? (int)$customerInput['area'] : null;
                $cityId = !empty($customerInput['wilayah']) ? (int)$customerInput['wilayah'] : null;
                $addressNotes = $customerInput['address'] ?? null;

                if (!empty($customerInput['phone'])) {
                    $customer = Customer::firstOrCreate(
                        ['phone' => $customerInput['phone']],
                        [
                            'name' => $customerInput['name'] ?? '',
                            'city_id' => $cityId,
                            'area_id' => $areaId,
                            'notes' => $addressNotes,
                        ]
                    );
                } else {
                    $customer = Customer::create([
                        'name' => $customerInput['name'] ?? '',
                        'city_id' => $cityId,
                        'area_id' => $areaId,
                        'notes' => $addressNotes,
                    ]);
                }

                if (!$customer->wasRecentlyCreated) {
                    $customer->update([
                        'name' => $customerInput['name'] ?? $customer->name,
                        'city_id' => $cityId ?? $customer->city_id,
                        'area_id' => $areaId ?? $customer->area_id,
                        'notes' => $addressNotes ?? $customer->notes,
                    ]);
                }

                $customerId = $customer->id;
            }

            $deliveryCharges = 0;
            $deliveryPaid = false;
            if ($request->input('order_type') === 'delivery') {
                $deliveryCharges = (float)($totals['delivery_charges'] ?? 0);
                $deliveryPaid = (bool)($totals['delivery_paid'] ?? false);
            }

            $selectedChannelId = session('pos_selected_channel_id', null);

            $order = PosOrders::create([
                'customer_id' => $customerId,
                'order_type' => $request->input('order_type', 'direct'),
                'item_count' => count($items),
                'paid_amount' => collect($payments)->sum('amount'),
                'total_amount' => $totals['total'] ?? 0,
                'discount_type' => data_get($request, 'discount.type'),
                'total_discount' => $totals['discount'] ?? 0,
                'delivery_charges' => $deliveryCharges,
                'delivery_paid' => $deliveryPaid,
                'profit' => null,
                'return_status' => 0,
                'restore_status' => 0,
                'order_no' => $orderNoInt,
                'notes' => $request->input('notes') ?? ($customerInput['address'] ?? null),
                'added_by' => $userName,
                'user_id' => $userId,
                'channel_id' => $selectedChannelId,
            ]);

            $subtotalAll = $totals['subtotal'] ?? ($totals['total'] ?? 0);
            $totalDiscount = $totals['discount'] ?? 0;
            $totalProfit = 0;

            foreach ($items as $item) {
                $qty = $item['qty'];
                $linePrice = $item['price'];
                $lineSubtotal = $linePrice * $qty;

                $discountShare = $subtotalAll > 0 ? ($totalDiscount * ($lineSubtotal / $subtotalAll)) : 0;
                $effectiveLine = $lineSubtotal - $discountShare;
                $unitEffectivePrice = $qty > 0 ? $effectiveLine / $qty : 0;

                $stock = Stock::find($item['id']);
                $unitCostPrice = (float)($stock->cost_price ?? 0);
                $unitTailorCharges = (float)($stock->tailor_charges ?? 0);

                $itemProfit = ($unitEffectivePrice - $unitCostPrice - $unitTailorCharges) * $qty;
                $totalProfit += $itemProfit;

                $colorId = null;
                if (!empty($item['color_id']) && is_numeric($item['color_id'])) {
                    $colorId = (int)$item['color_id'];
                }

                $sizeId = null;
                if (!empty($item['size_id']) && is_numeric($item['size_id'])) {
                    $sizeId = (int)$item['size_id'];
                }

                PosOrdersDetail::create([
                    'order_id' => $order->id,
                    'order_no' => $orderNoFormatted,
                    'item_id' => $item['id'],
                    'item_barcode' => $stock['barcode'] ?? '',
                    'item_quantity' => $qty,
                    'color_id' => $colorId,
                    'size_id' => $sizeId,
                    'item_discount_price' => $discountShare,
                    'item_price' => $linePrice,
                    'item_total' => $effectiveLine,
                    'item_tax' => $item['tax'] ?? 0,
                    'item_profit' => $itemProfit,
                    'added_by' => $userName,
                    'user_id' => $userId,
                    'branch_id' => $item['branch_id'] ?? null,
                    'channel_id' => $selectedChannelId,
                ]);

                if ($colorId && $sizeId) {
                    $colorSize = ColorSize::where('stock_id', $item['id'])
                        ->where('color_id', $colorId)
                        ->where('size_id', $sizeId)
                        ->first();

                    if ($colorSize) {
                        $currentQty = (int)($colorSize->qty ?? 0);
                        $newQty = max(0, $currentQty - $qty);
                        $colorSize->qty = $newQty;
                        $colorSize->save();

                        $stockTotalQty = ColorSize::where('stock_id', $item['id'])->sum('qty');
                        $stock->quantity = $stockTotalQty;
                        $stock->save();

                        StockAuditLog::create([
                            'stock_id' => $stock->id,
                            'abaya_code' => $stock->abaya_code,
                            'barcode' => $stock->barcode,
                            'design_name' => $stock->design_name,
                            'operation_type' => 'sold',
                            'previous_quantity' => $currentQty,
                            'new_quantity' => $newQty,
                            'quantity_change' => -$qty,
                            'related_id' => $orderNoFormatted,
                            'related_type' => 'pos_order',
                            'related_info' => ['order_id' => $order->id, 'customer_id' => $customerId],
                            'color_id' => $colorId,
                            'size_id' => $sizeId,
                            'user_id' => $userId,
                            'added_by' => $userName,
                            'notes' => 'Sold via POS',
                        ]);
                    }

                    if ($selectedChannelId) {
                        $channelStock = ChannelStock::where('location_type', 'channel')
                            ->where('location_id', $selectedChannelId)
                            ->where('stock_id', $item['id'])
                            ->where('color_id', $colorId)
                            ->where('size_id', $sizeId)
                            ->first();

                        if ($channelStock) {
                            $currentChannelQty = (int)($channelStock->quantity ?? 0);
                            $newChannelQty = max(0, $currentChannelQty - $qty);
                            $channelStock->quantity = $newChannelQty;
                            $channelStock->save();
                        }
                    }
                }
            }

            $order->profit = $totalProfit;
            $order->save();

            $totalAmount = $totals['total'] ?? 0;
            $totalDiscount = $totals['discount'] ?? 0;

            foreach ($payments as $pay) {
                PosPayment::create([
                    'order_id' => $order->id,
                    'order_no' => $orderNoFormatted,
                    'account_id' => $pay['account_id'],
                    'paid_amount' => $pay['amount'],
                    'total_amount' => $totalAmount,
                    'discount' => $totalDiscount,
                    'notes' => $pay['reference'] ?? null,
                    'added_by' => $userName,
                    'user_id' => $userId,
                ]);

                $account = Account::find($pay['account_id']);
                $accountTax = null;
                $accountTaxFee = null;

                if ($account && $account->commission && (float)$account->commission > 0) {
                    $commissionPercentage = (float)$account->commission;
                    $paymentAmount = (float)$pay['amount'];
                    $commissionAmount = ($commissionPercentage / 100) * $paymentAmount;
                    $accountTax = $commissionPercentage;
                    $accountTaxFee = $commissionAmount;
                }

                PosPaymentExpence::create([
                    'order_id' => $order->id,
                    'order_no' => $orderNoFormatted,
                    'total_amount' => $pay['amount'],
                    'accoun_id' => $pay['account_id'],
                    'account_tax' => $accountTax,
                    'account_tax_fee' => $accountTaxFee,
                    'added_by' => $userName,
                    'updated_by' => $userName,
                    'user_id' => $userId,
                ]);

                if ($account) {
                    $currentBalance = (float)($account->opening_balance ?? 0);
                    $newBalance = $currentBalance + (float)$pay['amount'];
                    $account->opening_balance = $newBalance;
                    $account->save();
                }
            }

            DB::commit();

            $customerContact = null;
            if ($order->customer && $order->customer->phone) {
                $customerContact = $order->customer->phone;
            } elseif (isset($customer) && $customer->phone) {
                $customerContact = $customer->phone;
            }

            $smsParams = [
                'sms_status' => 1,
                'order_id' => $order->id,
                'contact' => $customerContact,
            ];

            $smsContent = \get_sms($smsParams);

            if (!empty($customerContact) && !empty($smsContent)) {
                \sms_module($customerContact, $smsContent);
            }

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_no' => $orderNoFormatted,
                'message' => trans('messages.order_saved_successfully', [], session('locale')),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function ordersList()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(8, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        return view('pos::orders_list');
    }

    public function getOrdersList(Request $request)
    {
        try {
            $orders = PosOrders::with([
                'customer',
                'details.stock.images',
                'details.color',
                'details.size',
                'payments.account'
            ])
                ->orderBy('id', 'DESC')
                ->paginate(10);

            $formattedOrders = $orders->map(function ($order) {
                $orderNo = str_pad($order->order_no ?? $order->id, 6, '0', STR_PAD_LEFT);
                $customerName = $order->customer ? $order->customer->name : '-';
                $date = $order->created_at ? $order->created_at->format('Y-m-d') : '-';
                $time = $order->created_at ? $order->created_at->format('H:i:s') : '-';
                $itemsCount = $order->details->count();
                $subtotal = (float)($order->total_amount ?? 0) + (float)($order->total_discount ?? 0);
                $discount = (float)($order->total_discount ?? 0);
                $paidAmount = (float)($order->paid_amount ?? 0);
                $paymentMethods = $order->payments->map(function ($payment) {
                    return $payment->account ? $payment->account->account_name : 'Unknown';
                })->unique()->implode(', ');

                $orderTypeLabel = $order->order_type === 'delivery' ?
                    trans('messages.delivery', [], session('locale')) :
                    trans('messages.direct', [], session('locale'));

                return [
                    'id' => $order->id,
                    'order_no' => $orderNo,
                    'customer_name' => $customerName,
                    'order_type' => $orderTypeLabel,
                    'order_type_raw' => $order->order_type,
                    'delivery_status' => $order->delivery_status ?? null,
                    'date' => $date,
                    'time' => $time,
                    'items_count' => $itemsCount,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'total_amount' => (float)($order->total_amount ?? 0),
                    'paid_amount' => $paidAmount,
                    'payment_methods' => $paymentMethods ?: '-',
                    'items' => $order->details->map(function ($detail) {
                        $locale = session('locale', 'en');
                        $stock = $detail->stock;
                        $colorName = $detail->color ?
                            ($locale === 'ar' ? ($detail->color->color_name_ar ?? $detail->color->color_name_en) : ($detail->color->color_name_en ?? $detail->color->color_name_ar)) :
                            '-';
                        $sizeName = $detail->size ?
                            ($locale === 'ar' ? ($detail->size->size_name_ar ?? $detail->size->size_name_en) : ($detail->size->size_name_en ?? $detail->size->size_name_ar)) :
                            '-';

                        return [
                            'id' => $detail->id,
                            'stock_id' => $detail->item_id,
                            'abaya_code' => $stock ? ($stock->abaya_code ?? '-') : '-',
                            'design_name' => $stock ? ($stock->design_name ?? '-') : '-',
                            'barcode' => $detail->item_barcode ?? '-',
                            'quantity' => (int)($detail->item_quantity ?? 0),
                            'price' => (float)($detail->item_price ?? 0),
                            'total' => (float)($detail->item_total ?? 0),
                            'color_id' => $detail->color_id,
                            'color_name' => $colorName,
                            'size_id' => $detail->size_id,
                            'size_name' => $sizeName,
                            'image' => $stock && $stock->images->first() ? asset($stock->images->first()->image_path) : null,
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'orders' => $formattedOrders,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateDeliveryStatus(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:pos_orders,id',
                'delivery_status' => 'required|string|in:not_delivered,delivered,pending,shipped,under_preparation,under_repairing',
            ]);

            $order = PosOrders::findOrFail($request->order_id);

            if ($order->order_type !== 'delivery') {
                return response()->json([
                    'success' => false,
                    'message' => trans('messages.only_delivery_orders', [], session('locale')),
                ], 400);
            }

            $order->delivery_status = $request->delivery_status;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => trans('messages.delivery_status_updated', [], session('locale')),
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function pos_bill(Request $request)
    {
        $orderId = $request->input('order_id');

        if (!$orderId) {
            return view('bills.pos_bill', [
                'order' => null,
                'orderDetails' => [],
                'payments' => []
            ]);
        }

        $order = PosOrders::with([
            'customer',
            'channel',
            'details.stock.images',
            'details.color',
            'details.size',
            'payments.account'
        ])->find($orderId);

        if (!$order) {
            return view('bills.pos_bill', [
                'order' => null,
                'orderDetails' => [],
                'payments' => []
            ]);
        }

        $orderNo = str_pad($order->order_no ?? $order->id, 6, '0', STR_PAD_LEFT);

        $orderDetails = $order->details->map(function ($detail) {
            $locale = session('locale', 'en');
            $stock = $detail->stock;

            $colorName = $detail->color ?
                ($locale === 'ar' ? ($detail->color->color_name_ar ?? $detail->color->color_name_en) : ($detail->color->color_name_en ?? $detail->color->color_name_ar)) :
                '';
            $sizeName = $detail->size ?
                ($locale === 'ar' ? ($detail->size->size_name_ar ?? $detail->size->size_name_en) : ($detail->size->size_name_en ?? $detail->size->size_name_ar)) :
                '';

            return [
                'id' => $detail->id,
                'abaya_code' => $stock ? ($stock->abaya_code ?? '-') : '-',
                'design_name' => $stock ? ($stock->design_name ?? '-') : '-',
                'image' => $stock && $stock->images->first() ? asset($stock->images->first()->image_path) : null,
                'color_name' => $colorName,
                'size_name' => $sizeName,
                'quantity' => (int)($detail->item_quantity ?? 0),
                'unit_price' => (float)($detail->item_price ?? 0),
                'total' => (float)($detail->item_total ?? 0),
                'abaya_length' => null,
                'bust' => null,
                'sleeves' => null,
            ];
        });

        $payments = $order->payments->map(function ($payment) {
            return [
                'account_name' => $payment->account ? $payment->account->account_name : 'Unknown',
                'amount' => (float)($payment->paid_amount ?? 0),
            ];
        });

        return view('bills.pos_bill', [
            'order' => $order,
            'orderNo' => $orderNo,
            'orderDetails' => $orderDetails,
            'payments' => $payments,
            'customer' => $order->customer,
        ]);
    }

    public function getActiveChannels()
    {
        $selectedChannelId = session('pos_selected_channel_id', null);

        $channels = Channel::where('status_for_pos', 1)
            ->orderBy('id', 'ASC')
            ->get(['id', 'channel_name_ar', 'channel_name_en', 'status_for_pos']);

        return response()->json([
            'success' => true,
            'channels' => $channels,
            'selected_channel_id' => $selectedChannelId
        ]);
    }

    public function selectChannel(Request $request)
    {
        $channelId = $request->input('channel_id');

        if ($channelId === null || $channelId === '') {
            session()->forget('pos_selected_channel_id');
            return response()->json([
                'success' => true,
                'message' => trans('messages.channel_cleared', [], session('locale', 'en')),
                'selected_channel_id' => null
            ]);
        }

        $channel = Channel::where('id', $channelId)
            ->where('status_for_pos', 1)
            ->first();

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => trans('messages.channel_not_found', [], session('locale', 'en'))
            ], 404);
        }

        session(['pos_selected_channel_id' => $channelId]);

        $stockCount = ChannelStock::where('location_type', 'channel')
            ->where('location_id', $channelId)
            ->whereNotNull('stock_id')
            ->distinct()
            ->pluck('stock_id')
            ->count();

        return response()->json([
            'success' => true,
            'message' => trans('messages.channel_selected', [], session('locale', 'en')),
            'selected_channel_id' => $channelId,
            'channel_name' => session('locale') === 'ar'
                ? ($channel->channel_name_ar ?? $channel->channel_name_en)
                : ($channel->channel_name_en ?? $channel->channel_name_ar),
            'stock_count' => $stockCount
        ]);
    }

    private function hasAvailableQuantity($stock)
    {
        $mode = $stock->mode ?? 'color_size';

        if ($mode === 'size') {
            return $stock->sizes->sum('qty') > 0;
        } elseif ($mode === 'color') {
            return $stock->colors->sum('qty') > 0;
        } else {
            return $stock->colorSizes->sum('qty') > 0;
        }
    }

    /**
     * Get shipping/delivery fee from city's delivery_charges (no external API).
     */
    public function getShippingFee(Request $request)
    {
        $orderType = $request->input('order_type', 'direct');
        if ($orderType !== 'delivery') {
            return response()->json(['success' => true, 'shipping_fee' => 0]);
        }

        $customerInput = $request->input('customer', []);
        $cityId = !empty($customerInput['wilayah']) ? (int)$customerInput['wilayah'] : null;

        if (!$cityId) {
            return response()->json([
                'success' => false,
                'message' => 'City (wilayah) is required for delivery shipping fee',
            ], 422);
        }

        $city = City::find($cityId);
        $deliveryFee = $city ? (float)($city->delivery_charges ?? 0) : 0;

        return response()->json([
            'success' => true,
            'shipping_fee' => $deliveryFee,
        ]);
    }
}
