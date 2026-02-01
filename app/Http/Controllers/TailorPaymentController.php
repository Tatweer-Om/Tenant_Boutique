<?php

namespace App\Http\Controllers;

use App\Models\TailorPayment;
use App\Models\TailorPaymentItem;
use App\Models\StockHistory;
use App\Models\SpecialOrderItem;
use App\Models\Stock;
use App\Models\ColorSize;
use App\Models\Tailor;
use App\Models\Account;
use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TailorPaymentController extends Controller
{
    /**
     * Show the tailor payments page
     */
    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $permissions = Auth::user()->permissions ?? [];

        if (!in_array(12, $permissions)) { // Assuming 12 is tailor permission
            return redirect()->route('login_page')->with('error', 'Permission denied');
        }

        return view('tailor_payments.index');
    }

    /**
     * Get all accounts with their balances
     */
    public function getAccounts()
    {
        try {
            $accounts = Account::where('account_status', 1) // Active accounts only
                ->orderBy('account_name', 'ASC')
                ->get()
                ->map(function($account) {
                    return [
                        'id' => $account->id,
                        'name' => $account->account_name,
                        'branch' => $account->account_branch ?? '',
                        'account_no' => $account->account_no ?? '',
                        'balance' => (float)($account->opening_balance ?? 0),
                    ];
                });

            return response()->json([
                'success' => true,
                'accounts' => $accounts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching accounts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending payments (abayas sent to tailor - processing status)
     * Each special order item sent to tailor is shown as a separate row
     */
    public function getPendingPayments(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $perPage = 10;
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');

            $from = $fromDate ? \Carbon\Carbon::parse($fromDate)->startOfDay() : null;
            $to = $toDate ? \Carbon\Carbon::parse($toDate)->endOfDay() : null;

            // Get all paid special_order_item_ids
            $paidSpecialOrderItemIds = TailorPaymentItem::whereNotNull('special_order_item_id')
                ->pluck('special_order_item_id')
                ->toArray();

            // Get all paid stock_history_ids
            $paidStockHistoryIds = TailorPaymentItem::whereNotNull('stock_history_id')
                ->pluck('stock_history_id')
                ->toArray();

            $pendingPayments = [];

            // Get abayas sent to tailor (processing status) - not yet received
            // These are items that have been sent to tailor but not yet received back
            $itemsSentToTailor = SpecialOrderItem::with(['specialOrder.customer', 'stock.images', 'tailor'])
                ->whereNotNull('tailor_id')
                ->where('tailor_status', 'processing') // Items sent to tailor
                ->whereNotNull('sent_to_tailor_at') // Must have been sent
                ->whereNull('received_from_tailor_at') // Not yet received
                ->where('quantity', '>', 0) // Only include items with quantity > 0
                ->whereNotIn('id', $paidSpecialOrderItemIds) // Exclude already paid items
                ->orderBy('sent_to_tailor_at', 'DESC')
                ->get();

            // Get abayas received from tailor (received status)
            // These are items that have been received from tailor and are ready for payment
            $itemsReceivedFromTailor = SpecialOrderItem::with(['specialOrder.customer', 'stock.images', 'tailor'])
                ->whereNotNull('tailor_id')
                ->where(function($query) {
                    $query->where('tailor_status', 'received')
                          ->orWhereNotNull('received_from_tailor_at');
                })
                ->where('quantity', '>', 0) // Only include items with quantity > 0
                ->whereNotIn('id', $paidSpecialOrderItemIds) // Exclude already paid items
                ->orderBy('received_from_tailor_at', 'DESC')
                ->get();

            // Process each item sent to tailor as a separate row
            foreach ($itemsSentToTailor as $item) {
                if (!$item->stock) continue;
                
                $abayaCode = $item->abaya_code ?? ($item->stock->abaya_code ?? 'N/A');
                $tailorId = $item->tailor_id;
                
                // Skip if no tailor_id found
                if (!$tailorId) continue;

                $quantity = $item->quantity ?? 0;
                $stock = $item->stock;
                $unitCharge = $stock->tailor_charges ?? 0;
                $totalCharge = $quantity * $unitCharge;

                // Get tailor order number (for Order No column)
                $orderNo = $item->tailor_order_no ?? '—';
                if ($orderNo === '—' && $item->specialOrder) {
                    $orderDate = \Carbon\Carbon::parse($item->specialOrder->created_at);
                    $orderNo = $orderDate->format('Y') . '-' . str_pad($item->specialOrder->id, 4, '0', STR_PAD_LEFT);
                }

                // Get special order number (for Source column)
                $specialOrderNo = '—';
                if ($item->specialOrder) {
                    if ($item->specialOrder->special_order_no) {
                        $specialOrderNo = $item->specialOrder->special_order_no;
                    } else {
                        // Fallback to generated format
                        $orderDate = \Carbon\Carbon::parse($item->specialOrder->created_at);
                        $specialOrderNo = $orderDate->format('Y') . '-' . str_pad($item->specialOrder->id, 4, '0', STR_PAD_LEFT);
                    }
                }

                // Determine source type (use special_order_no for source)
                $source = 'Special Order';
                if ($item->specialOrder) {
                    $source = 'Special Order: ' . $specialOrderNo;
                }

                // Get customer information
                $customerName = 'N/A';
                $customerPhone = 'N/A';
                $isStockOrder = false;
                
                if ($item->specialOrder) {
                    $isStockOrder = ($item->specialOrder->customer_id === null || $item->specialOrder->source === 'stock');
                    if (!$isStockOrder && $item->specialOrder->customer) {
                        $customerName = $item->specialOrder->customer->name ?? 'N/A';
                        $customerPhone = $item->specialOrder->customer->phone ?? 'N/A';
                    } else if ($isStockOrder) {
                        $customerName = trans('messages.stock_special_order', [], session('locale', 'en'));
                        $customerPhone = '—';
                    }
                }

                // Get abaya image
                $image = '/images/placeholder.png';
                if ($stock && $stock->images && $stock->images->count() > 0) {
                    $firstImage = $stock->images->first();
                    if ($firstImage && $firstImage->image_path) {
                        $image = $firstImage->image_path;
                    }
                }

                // Create a separate row for each item sent to tailor
                $pendingPayments[] = [
                    'id' => 'special_order_' . $item->id, // Unique ID for this entry
                    'stock_history_id' => null,
                    'special_order_item_id' => $item->id,
                    'type' => 'special_order',
                    'order_no' => $orderNo,
                    'abaya_code' => $abayaCode,
                    'abaya_name' => $item->design_name ?? ($stock->design_name ?? $abayaCode),
                    'abaya_image' => $image,
                    'tailor_id' => $tailorId,
                    'tailor_name' => $item->tailor ? $item->tailor->tailor_name : 'N/A',
                    'quantity' => $quantity,
                    'unit_charge' => $unitCharge,
                    'total_charge' => $totalCharge,
                    'source' => $source,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'length' => $item->abaya_length ?? '—',
                    'bust' => $item->bust ?? '—',
                    'sleeves' => $item->sleeves_length ?? '—',
                    'buttons' => $item->buttons ?? false,
                    'notes' => $item->notes ?? '—',
                    'date' => $item->sent_to_tailor_at ? $item->sent_to_tailor_at->format('Y-m-d') : ($item->specialOrder ? $item->specialOrder->created_at->format('Y-m-d') : 'N/A'),
                    'created_at' => $item->sent_to_tailor_at ? $item->sent_to_tailor_at->toDateTimeString() : ($item->specialOrder ? $item->specialOrder->created_at->toDateTimeString() : now()->toDateTimeString()),
                ];
            }

            // Process each item received from tailor as a separate row
            foreach ($itemsReceivedFromTailor as $item) {
                if (!$item->stock) continue;
                
                $abayaCode = $item->abaya_code ?? ($item->stock->abaya_code ?? 'N/A');
                $tailorId = $item->tailor_id;
                
                // Skip if no tailor_id found
                if (!$tailorId) continue;

                $quantity = $item->quantity ?? 0;
                $stock = $item->stock;
                $unitCharge = $stock->tailor_charges ?? 0;
                $totalCharge = $quantity * $unitCharge;

                // Get tailor order number (for Order No column)
                $orderNo = $item->tailor_order_no ?? '—';
                if ($orderNo === '—' && $item->specialOrder) {
                    $orderDate = \Carbon\Carbon::parse($item->specialOrder->created_at);
                    $orderNo = $orderDate->format('Y') . '-' . str_pad($item->specialOrder->id, 4, '0', STR_PAD_LEFT);
                }

                // Get special order number (for Source column)
                $specialOrderNo = '—';
                if ($item->specialOrder) {
                    if ($item->specialOrder->special_order_no) {
                        $specialOrderNo = $item->specialOrder->special_order_no;
                    } else {
                        // Fallback to generated format
                        $orderDate = \Carbon\Carbon::parse($item->specialOrder->created_at);
                        $specialOrderNo = $orderDate->format('Y') . '-' . str_pad($item->specialOrder->id, 4, '0', STR_PAD_LEFT);
                    }
                }

                // Determine source type (use special_order_no for source)
                $source = 'Special Order';
                if ($item->specialOrder) {
                    $source = 'Special Order: ' . $specialOrderNo;
                }

                // Get customer information
                $customerName = 'N/A';
                $customerPhone = 'N/A';
                $isStockOrder = false;
                
                if ($item->specialOrder) {
                    $isStockOrder = ($item->specialOrder->customer_id === null || $item->specialOrder->source === 'stock');
                    if (!$isStockOrder && $item->specialOrder->customer) {
                        $customerName = $item->specialOrder->customer->name ?? 'N/A';
                        $customerPhone = $item->specialOrder->customer->phone ?? 'N/A';
                    } else if ($isStockOrder) {
                        $customerName = trans('messages.stock_special_order', [], session('locale', 'en'));
                        $customerPhone = '—';
                    }
                }

                // Get abaya image
                $image = '/images/placeholder.png';
                if ($stock && $stock->images && $stock->images->count() > 0) {
                    $firstImage = $stock->images->first();
                    if ($firstImage && $firstImage->image_path) {
                        $image = $firstImage->image_path;
                    }
                }

                // Use received date if available, otherwise use sent date or order date
                $receivedDate = $item->received_from_tailor_at ? $item->received_from_tailor_at->format('Y-m-d') : ($item->sent_to_tailor_at ? $item->sent_to_tailor_at->format('Y-m-d') : ($item->specialOrder ? $item->specialOrder->created_at->format('Y-m-d') : 'N/A'));
                $createdAt = $item->received_from_tailor_at ? $item->received_from_tailor_at->toDateTimeString() : ($item->sent_to_tailor_at ? $item->sent_to_tailor_at->toDateTimeString() : ($item->specialOrder ? $item->specialOrder->created_at->toDateTimeString() : now()->toDateTimeString()));

                // Create a separate row for each item received from tailor
                $pendingPayments[] = [
                    'id' => 'special_order_' . $item->id, // Unique ID for this entry
                    'stock_history_id' => null,
                    'special_order_item_id' => $item->id,
                    'type' => 'special_order',
                    'order_no' => $orderNo,
                    'abaya_code' => $abayaCode,
                    'abaya_name' => $item->design_name ?? ($stock->design_name ?? $abayaCode),
                    'abaya_image' => $image,
                    'tailor_id' => $tailorId,
                    'tailor_name' => $item->tailor ? $item->tailor->tailor_name : 'N/A',
                    'quantity' => $quantity,
                    'unit_charge' => $unitCharge,
                    'total_charge' => $totalCharge,
                    'source' => $source,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'length' => $item->abaya_length ?? '—',
                    'bust' => $item->bust ?? '—',
                    'sleeves' => $item->sleeves_length ?? '—',
                    'buttons' => $item->buttons ?? false,
                    'notes' => $item->notes ?? '—',
                    'date' => $receivedDate,
                    'created_at' => $createdAt,
                ];
            }

            // Get stock additions with tailor_id (from StockHistory)
            // These are stock additions that have a tailor assigned and haven't been paid yet
            $stockAdditions = StockHistory::with(['stock.images', 'tailor', 'color', 'size'])
                ->where('action_type', 1) // 1 = addition
                ->whereNotNull('tailor_id') // Must have a tailor assigned
                ->where('changed_qty', '>', 0) // Only include items with quantity > 0
                ->whereNotIn('id', $paidStockHistoryIds) // Exclude already paid items
                ->orderBy('created_at', 'DESC')
                ->get();

            // Process each stock addition as a separate row
            foreach ($stockAdditions as $history) {
                if (!$history->stock) continue;
                
                $abayaCode = $history->stock->abaya_code ?? 'N/A';
                $tailorId = $history->tailor_id;
                
                // Skip if no tailor_id found
                if (!$tailorId) continue;

                // Use current ColorSize qty so that pulls are reflected (pending = what's left in stock to pay for)
                if ($history->color_id !== null && $history->size_id !== null) {
                    $cs = ColorSize::where('stock_id', $history->stock_id)
                        ->where('color_id', $history->color_id)
                        ->where('size_id', $history->size_id)
                        ->first();
                    $quantity = $cs ? (int) $cs->qty : 0;
                } else {
                    $quantity = (int) ($history->changed_qty ?? 0);
                }
                if ($quantity <= 0) {
                    continue; // nothing left (e.g. all pulled) – skip from pending
                }

                $stock = $history->stock;
                $unitCharge = $stock->tailor_charges ?? 0;
                $totalCharge = $quantity * $unitCharge;

                // Generate order number for stock addition (use stock ID and date)
                $orderDate = \Carbon\Carbon::parse($history->created_at);
                $orderNo = 'ST-' . $orderDate->format('Ymd') . '-' . str_pad($history->id, 4, '0', STR_PAD_LEFT);

                // Source is stock addition
                $source = 'Stock Addition';

                // Get abaya image
                $image = '/images/placeholder.png';
                if ($stock && $stock->images && $stock->images->count() > 0) {
                    $firstImage = $stock->images->first();
                    if ($firstImage && $firstImage->image_path) {
                        $image = $firstImage->image_path;
                    }
                }

                // Get color and size names
                $colorName = '—';
                $sizeName = '—';
                if ($history->color) {
                    $locale = session('locale', 'en');
                    $colorName = $locale === 'ar' ? ($history->color->color_name_ar ?? $history->color->color_name_en) : ($history->color->color_name_en ?? $history->color->color_name_ar);
                }
                if ($history->size) {
                    $locale = session('locale', 'en');
                    $sizeName = $locale === 'ar' ? ($history->size->size_name_ar ?? $history->size->size_name_en) : ($history->size->size_name_en ?? $history->size->size_name_ar);
                }

                // Create a separate row for each stock addition
                $pendingPayments[] = [
                    'id' => 'stock_history_' . $history->id, // Unique ID for this entry
                    'stock_history_id' => $history->id,
                    'special_order_item_id' => null,
                    'type' => 'stock',
                    'order_no' => $orderNo,
                    'abaya_code' => $abayaCode,
                    'abaya_name' => $stock->design_name ?? $abayaCode,
                    'abaya_image' => $image,
                    'tailor_id' => $tailorId,
                    'tailor_name' => $history->tailor ? $history->tailor->tailor_name : 'N/A',
                    'quantity' => $quantity,
                    'unit_charge' => $unitCharge,
                    'total_charge' => $totalCharge,
                    'source' => $source,
                    'customer_name' => trans('messages.stock_special_order', [], session('locale', 'en')),
                    'customer_phone' => '—',
                    'length' => '—',
                    'bust' => '—',
                    'sleeves' => '—',
                    'buttons' => false,
                    'notes' => 'Color: ' . $colorName . ', Size: ' . $sizeName,
                    'date' => $history->created_at->format('Y-m-d'),
                    'created_at' => $history->created_at->toDateTimeString(),
                ];
            }

            // Apply date filter (inclusive) on the row's created_at date
            if ($from || $to) {
                $pendingPayments = array_values(array_filter($pendingPayments, function ($row) use ($from, $to) {
                    if (!isset($row['created_at']) || !$row['created_at']) {
                        return false;
                    }
                    $dt = \Carbon\Carbon::parse($row['created_at']);
                    if ($from && $dt->lt($from)) {
                        return false;
                    }
                    if ($to && $dt->gt($to)) {
                        return false;
                    }
                    return true;
                }));
            }

            // Sort by date (newest first), then by order number
            usort($pendingPayments, function($a, $b) {
                $dateCompare = strcmp($b['created_at'], $a['created_at']);
                if ($dateCompare !== 0) {
                    return $dateCompare;
                }
                return strcmp($a['order_no'], $b['order_no']);
            });

            // Paginate
            $total = count($pendingPayments);
            $offset = ($page - 1) * $perPage;
            $paginated = array_slice($pendingPayments, $offset, $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginated,
                'current_page' => (int)$page,
                'last_page' => (int)ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching pending payments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment history
     */
    public function getPaymentHistory(Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $perPage = 10;

            $payments = TailorPayment::with(['items.tailor', 'items.stock.images', 'items.specialOrderItem.specialOrder.customer', 'items.stockHistory.stock', 'account'])
                ->orderBy('payment_date', 'DESC')
                ->orderBy('id', 'DESC')
                ->paginate($perPage, ['*'], 'page', $page);

            $formattedPayments = $payments->map(function($payment) {
                // Group items by abaya_code and tailor
                $groupedItems = [];
                
                foreach ($payment->items as $item) {
                    $key = $item->abaya_code . '_' . $item->tailor_id;
                    
                    if (!isset($groupedItems[$key])) {
                        // Get customer information from special order item if available
                        $customerName = 'N/A';
                        $customerPhone = 'N/A';
                        $orderNo = '—';
                        $abayaImage = '/images/placeholder.png';
                        $length = '—';
                        $bust = '—';
                        $sleeves = '—';
                        $buttons = false;
                        $notes = '—';
                        
                        // Handle special order items
                        if ($item->specialOrderItem && $item->specialOrderItem->specialOrder) {
                            $specialOrder = $item->specialOrderItem->specialOrder;
                            $specialOrderItem = $item->specialOrderItem;
                            // Get tailor order number (preferred) or special order number (fallback)
                            $orderNo = $specialOrderItem->tailor_order_no ?? '—';
                            if ($orderNo === '—') {
                                $orderDate = \Carbon\Carbon::parse($specialOrder->created_at);
                                $orderNo = $orderDate->format('Y') . '-' . str_pad($specialOrder->id, 4, '0', STR_PAD_LEFT);
                            }
                            
                            $isStockOrder = ($specialOrder->customer_id === null || $specialOrder->source === 'stock');
                            if (!$isStockOrder && $specialOrder->customer) {
                                $customerName = $specialOrder->customer->name ?? 'N/A';
                                $customerPhone = $specialOrder->customer->phone ?? 'N/A';
                            } else if ($isStockOrder) {
                                $customerName = trans('messages.stock_special_order', [], session('locale', 'en'));
                                $customerPhone = '—';
                            }
                            
                            // Get abaya details from special order item
                            $length = $specialOrderItem->abaya_length ?? '—';
                            $bust = $specialOrderItem->bust ?? '—';
                            $sleeves = $specialOrderItem->sleeves_length ?? '—';
                            $buttons = $specialOrderItem->buttons ?? false;
                            $notes = $specialOrderItem->notes ?? '—';
                        }
                        // Handle stock history items (stock additions)
                        elseif ($item->stockHistory && $item->stockHistory->stock) {
                            $stockHistory = $item->stockHistory;
                            $orderDate = \Carbon\Carbon::parse($stockHistory->created_at);
                            $orderNo = 'ST-' . $orderDate->format('Ymd') . '-' . str_pad($stockHistory->id, 4, '0', STR_PAD_LEFT);
                            
                            $customerName = trans('messages.stock_special_order', [], session('locale', 'en'));
                            $customerPhone = '—';
                            
                            // Get color and size names for notes
                            $colorName = '—';
                            $sizeName = '—';
                            if ($stockHistory->color) {
                                $locale = session('locale', 'en');
                                $colorName = $locale === 'ar' ? ($stockHistory->color->color_name_ar ?? $stockHistory->color->color_name_en) : ($stockHistory->color->color_name_en ?? $stockHistory->color->color_name_ar);
                            }
                            if ($stockHistory->size) {
                                $locale = session('locale', 'en');
                                $sizeName = $locale === 'ar' ? ($stockHistory->size->size_name_ar ?? $stockHistory->size->size_name_en) : ($stockHistory->size->size_name_en ?? $stockHistory->size->size_name_ar);
                            }
                            $notes = 'Color: ' . $colorName . ', Size: ' . $sizeName;
                        }
                        
                        // Get abaya image
                        if ($item->stock && $item->stock->images && $item->stock->images->count() > 0) {
                            $firstImage = $item->stock->images->first();
                            if ($firstImage && $firstImage->image_path) {
                                $abayaImage = $firstImage->image_path;
                            }
                        }
                        
                        $groupedItems[$key] = [
                            'abaya_code' => $item->abaya_code,
                            'abaya_name' => $item->stock ? ($item->stock->design_name ?? $item->abaya_code) : $item->abaya_code,
                            'abaya_image' => $abayaImage,
                            'tailor_name' => $item->tailor->tailor_name ?? 'N/A',
                            'order_no' => $orderNo,
                            'customer_name' => $customerName,
                            'customer_phone' => $customerPhone,
                            'length' => $length,
                            'bust' => $bust,
                            'sleeves' => $sleeves,
                            'buttons' => $buttons,
                            'notes' => $notes,
                            'total_quantity' => 0,
                            'unit_charge' => (float)($item->unit_charge ?? 0),
                            'total_charge' => 0,
                            'sources' => []
                        ];
                    }
                    
                    $groupedItems[$key]['total_quantity'] += $item->quantity;
                    $groupedItems[$key]['total_charge'] += $item->total_charge;
                    $groupedItems[$key]['sources'][] = $item->source === 'stock' ? 'Stock Addition' : 'Special Order';
                }

                return [
                    'id' => $payment->id,
                    'payment_date' => $payment->payment_date->format('Y-m-d'),
                    'total_amount' => (float)$payment->total_amount,
                    'payment_method' => $payment->payment_method ?? 'N/A',
                    'account_name' => $payment->account ? $payment->account->account_name : ($payment->payment_method ?? 'N/A'),
                    'notes' => $payment->notes ?? '',
                    'added_by' => $payment->added_by ?? 'N/A',
                    'items' => array_values($groupedItems)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPayments,
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request)
    {
        try {
            DB::beginTransaction();

            $user_id = Auth::id();
            $user = Auth::user();
            $user_name = $user ? $user->user_name : 'System';

            $selectedItems = $request->input('selected_items', []);
            $accountId = $request->input('account_id');
            
            if (empty($selectedItems)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one item to pay'
                ], 422);
            }

            // Validate account is provided
            if (!$accountId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select an account for payment'
                ], 422);
            }

            // Get account and validate it exists
            $account = Account::find($accountId);
            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected account not found'
                ], 422);
            }

            // Get current account balance for validation
            $currentBalance = (float)($account->opening_balance ?? 0);

            // Process selected items - each item is a special order item sent to tailor
            $itemsToPay = [];
            $totalAmount = 0;

            foreach ($selectedItems as $item) {
                $specialOrderItemId = $item['special_order_item_id'] ?? null;
                $stockHistoryId = $item['stock_history_id'] ?? null;
                
                // Handle special order items
                if ($specialOrderItemId) {
                    // Get the special order item
                    $orderItem = SpecialOrderItem::with('stock')->find($specialOrderItemId);
                    if (!$orderItem) {
                        continue;
                    }

                    // Verify it's either in processing status (sent to tailor, not received) OR received status
                    // Allow both processing and received items to be paid
                    $isProcessing = ($orderItem->tailor_status === 'processing' && $orderItem->received_from_tailor_at === null);
                    $isReceived = ($orderItem->tailor_status === 'received' || $orderItem->received_from_tailor_at !== null);
                    
                    if (!$isProcessing && !$isReceived) {
                        continue;
                    }

                    // Check if already paid
                    $alreadyPaid = TailorPaymentItem::where('special_order_item_id', $specialOrderItemId)->exists();
                    if ($alreadyPaid) {
                        continue;
                    }

                    $quantity = (int)($item['quantity'] ?? $orderItem->quantity ?? 0);
                    $unitCharge = (float)($item['unit_charge'] ?? ($orderItem->stock->tailor_charges ?? 0));
                    $totalCharge = $quantity * $unitCharge;

                    $itemsToPay[] = [
                        'type' => 'special_order',
                        'special_order_item_id' => $orderItem->id,
                        'stock_history_id' => null,
                        'tailor_id' => $orderItem->tailor_id,
                        'stock_id' => $orderItem->stock_id,
                        'abaya_code' => $orderItem->abaya_code ?? ($orderItem->stock->abaya_code ?? 'N/A'),
                        'quantity' => $quantity,
                        'unit_charge' => $unitCharge,
                        'total_charge' => $totalCharge
                    ];

                    $totalAmount += $totalCharge;
                }
                // Handle stock history items (stock additions)
                elseif ($stockHistoryId) {
                    // Get the stock history item
                    $historyItem = StockHistory::with('stock')->find($stockHistoryId);
                    if (!$historyItem || !$historyItem->stock) {
                        continue;
                    }

                    // Verify it's an addition (action_type = 1) and has a tailor
                    if ($historyItem->action_type !== 1 || !$historyItem->tailor_id) {
                        continue;
                    }

                    // Check if already paid
                    $alreadyPaid = TailorPaymentItem::where('stock_history_id', $stockHistoryId)->exists();
                    if ($alreadyPaid) {
                        continue;
                    }

                    $quantity = (int)($item['quantity'] ?? $historyItem->changed_qty ?? 0);
                    $unitCharge = (float)($item['unit_charge'] ?? ($historyItem->stock->tailor_charges ?? 0));
                    $totalCharge = $quantity * $unitCharge;

                    $itemsToPay[] = [
                        'type' => 'stock',
                        'special_order_item_id' => null,
                        'stock_history_id' => $historyItem->id,
                        'tailor_id' => $historyItem->tailor_id,
                        'stock_id' => $historyItem->stock_id,
                        'abaya_code' => $historyItem->stock->abaya_code ?? 'N/A',
                        'quantity' => $quantity,
                        'unit_charge' => $unitCharge,
                        'total_charge' => $totalCharge
                    ];

                    $totalAmount += $totalCharge;
                }
            }

            // Validate account has sufficient balance
            if ($currentBalance < $totalAmount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance in account. Available: ' . number_format($currentBalance, 3) . ', Required: ' . number_format($totalAmount, 3)
                ], 422);
            }

            // Create payment record
            $payment = TailorPayment::create([
                'payment_date' => $request->input('payment_date', now()->format('Y-m-d')),
                'total_amount' => $totalAmount,
                'payment_method' => $request->input('payment_method', 'cash'),
                'account_id' => $accountId,
                'notes' => $request->input('notes', ''),
                'user_id' => $user_id ? (string)$user_id : null,
                'added_by' => $user_name,
            ]);

            // Create payment items - one for each individual entry
            foreach ($itemsToPay as $item) {
                TailorPaymentItem::create([
                    'tailor_payment_id' => $payment->id,
                    'tailor_id' => $item['tailor_id'],
                    'stock_id' => $item['stock_id'] ?? null,
                    'special_order_item_id' => $item['special_order_item_id'] ?? null,
                    'stock_history_id' => $item['stock_history_id'] ?? null,
                    'abaya_code' => $item['abaya_code'],
                    'quantity' => $item['quantity'],
                    'unit_charge' => $item['unit_charge'],
                    'total_charge' => $item['total_charge'],
                    'source' => $item['type'] === 'stock' ? 'stock' : 'special_order',
                ]);
            }

            // Deduct amount from account
            $newBalance = $currentBalance - $totalAmount;
            $account->opening_balance = $newBalance;
            $account->updated_by = $user_name;
            $account->save();

            // Create balance record for tracking
            Balance::create([
                'account_name' => $account->account_name ?? '',
                'account_id' => $account->id,
                'account_no' => $account->account_no ?? '',
                'previous_balance' => $currentBalance,
                'new_total_amount' => $newBalance,
                'source' => 'Tailor Payment',
                'expense_amount' => $totalAmount,
                'expense_name' => 'Tailor Payment - Payment ID: ' . $payment->id,
                'expense_date' => $request->input('payment_date', now()->format('Y-m-d')),
                'expense_added_by' => $user_name,
                'notes' => 'Payment to tailors: ' . ($request->input('notes', '')),
                'added_by' => $user_name,
                'user_id' => $user_id ? (string)$user_id : null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_id' => $payment->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
