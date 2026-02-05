<?php

namespace Modules\Boutique\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\BoutiqueInvo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoutiqueController extends Controller
{
    /**
     * Normalize invoice month to canonical "Y-m".
     * Supports stored formats: "m-Y" and "Y-m".
     */
    private function normalizeInvoiceMonthToYm(?string $month): ?string
    {
        $month = trim((string)$month);
        if ($month === '') return null;

        // "Y-m" (e.g. 2026-01)
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $month;
        }

        // "m-Y" (e.g. 01-2026)
        if (preg_match('/^\d{2}-\d{4}$/', $month)) {
            [$m, $y] = explode('-', $month);
            return $y . '-' . $m;
        }

        // Last resort: try parsing
        try {
            return Carbon::parse($month)->format('Y-m');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function formatYmToMY(string $ym): string
    {
        try {
            return Carbon::createFromFormat('Y-m', $ym)->format('m-Y');
        } catch (\Throwable $e) {
            return $ym;
        }
    }

 public function index() {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(11, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        return view('boutique::boutique');
    }


     public function boutique_profile($id) {
        $boutique = Boutique::findOrFail($id);
        
        // Get transfers to this boutique (shipments)
        $boutiqueLocation = 'boutique-' . $id;
        $transfers = \App\Models\Transfer::where('to', $boutiqueLocation)->get();
        
        // Calculate total abayas sent (sum of quantities from transfers)
        $totalAbayas = $transfers->sum('quantity');
        
        // Count number of shipments
        $numberOfShipments = $transfers->count();
        
        // Get unpaid rent invoice status
        $unpaidRentInvoice = ($boutique->rent_invoice_status == '5') ? 1 : 0;
        
        // Get last 6 months sales data from settlements
        $last6Months = [];
        $monthNames = [];
        $salesData = [];
        
        // Month name mapping for translations
        $monthTranslationKeys = [
            'January' => 'january',
            'February' => 'february',
            'March' => 'march',
            'April' => 'april',
            'May' => 'may',
            'June' => 'june',
            'July' => 'july',
            'August' => 'august',
            'September' => 'september',
            'October' => 'october',
            'November' => 'november',
            'December' => 'december'
        ];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthKey = $date->format('Y-m');
            $monthNameEn = $date->format('F');
            $translationKey = $monthTranslationKeys[$monthNameEn] ?? strtolower($monthNameEn);
            
            // Get settlement for this month and boutique
            $settlement = \App\Models\Settlement::where('boutique_id', $id)
                ->where('month', $monthKey)
                ->first();
            
            $salesAmount = $settlement ? (float)$settlement->total_sales : 0;
            
            $last6Months[] = [
                'month' => $monthKey,
                'name' => $monthNameEn,
                'translation_key' => $translationKey,
                'sales' => $salesAmount
            ];
            $monthNames[] = $translationKey;
            $salesData[] = $salesAmount;
        }
        
        // Calculate total sales from all settlements
        $totalSales = \App\Models\Settlement::where('boutique_id', $id)
            ->sum('total_sales');
        
        // Calculate most selling color and most requested size from settlements
        $colorCounts = [];
        $sizeCounts = [];
        
        $settlements = \App\Models\Settlement::where('boutique_id', $id)->get();
        
        foreach ($settlements as $settlement) {
            if ($settlement->items_data) {
                $itemsData = is_string($settlement->items_data) 
                    ? json_decode($settlement->items_data, true) 
                    : $settlement->items_data;
                
                if (is_array($itemsData)) {
                    foreach ($itemsData as $item) {
                        // Count colors
                        if (isset($item['color']) && !empty($item['color'])) {
                            $colorName = $item['color'];
                            if (!isset($colorCounts[$colorName])) {
                                $colorCounts[$colorName] = 0;
                            }
                            // Add sold quantity if available, otherwise add 1
                            $colorCounts[$colorName] += isset($item['sold']) ? (int)$item['sold'] : 1;
                        }
                        
                        // Count sizes
                        if (isset($item['size']) && !empty($item['size'])) {
                            $sizeName = $item['size'];
                            if (!isset($sizeCounts[$sizeName])) {
                                $sizeCounts[$sizeName] = 0;
                            }
                            // Add sold quantity if available, otherwise add 1
                            $sizeCounts[$sizeName] += isset($item['sold']) ? (int)$item['sold'] : 1;
                        }
                    }
                }
            }
        }
        
        // Get most selling color
        $bestSellingColor = '-';
        if (!empty($colorCounts)) {
            arsort($colorCounts);
            $bestSellingColor = array_key_first($colorCounts);
        }
        
        // Get most requested size
        $mostRequestedSize = '-';
        if (!empty($sizeCounts)) {
            arsort($sizeCounts);
            $mostRequestedSize = array_key_first($sizeCounts);
        }
        
        $monthlyProfit = 0;
        
        // Invoices: show months up to current month, and show next month only within 10 days before due date.
        // Also ignore any invoices before rent start month.
        $today = \Carbon\Carbon::now()->startOfDay();
        $rentStartDate = $boutique->rent_date ? \Carbon\Carbon::parse($boutique->rent_date)->startOfDay() : null;
        $rentStartMonthYm = $rentStartDate ? $rentStartDate->copy()->startOfMonth()->format('Y-m') : null;
        $dueDay = $rentStartDate ? (int)$rentStartDate->day : 1;

        $nowMonth = \Carbon\Carbon::now()->startOfMonth();
        if ($rentStartDate && $nowMonth->lt($rentStartDate->copy()->startOfMonth())) {
            $nowMonth = $rentStartDate->copy()->startOfMonth();
        }
        $currentYm = $nowMonth->format('Y-m');
        $nextMonth = $nowMonth->copy()->addMonthNoOverflow();
        $nextYm = $nextMonth->format('Y-m');

        // Next month's due date (overflow-safe)
        $nextDueDate = \Carbon\Carbon::create($nextMonth->year, $nextMonth->month, $dueDay);
        if ((int)$nextDueDate->month !== (int)$nextMonth->month) {
            $nextDueDate = $nextMonth->copy()->endOfMonth();
        }
        $daysUntilNextDue = $today->diffInDays($nextDueDate->copy()->startOfDay(), false);
        $showNextMonth = ($daysUntilNextDue >= 0 && $daysUntilNextDue <= 10);

        // Load all invoice records and consolidate by month (support mixed formats).
        $rawInvoices = BoutiqueInvo::where('boutique_id', (string)$id)->get();
        $groups = []; // ym => Collection<Model>
        foreach ($rawInvoices as $inv) {
            $ym = $this->normalizeInvoiceMonthToYm($inv->month);
            if (!$ym) continue;
            if ($rentStartMonthYm && $ym < $rentStartMonthYm) continue;
            if (!isset($groups[$ym])) $groups[$ym] = collect();
            $groups[$ym]->push($inv);
        }

        $chosenByMonth = collect();
        foreach ($groups as $ym => $list) {
            $chosen = $list->first(function ($x) {
                return (string)($x->status ?? '') === '4' || (int)($x->status ?? 0) === 4;
            }) ?? $list->first(function ($x) {
                return (string)($x->status ?? '') === '5' || (int)($x->status ?? 0) === 5;
            }) ?? $list->sortByDesc('id')->first();

            if ($chosen) {
                $chosenByMonth->put($ym, $chosen);
            }
        }

        // Filter months shown in profile invoices table
        $allInvoices = $chosenByMonth
            ->filter(function ($inv, $ym) use ($currentYm, $nextYm, $showNextMonth) {
                if ($ym <= $currentYm) return true;
                if ($showNextMonth && $ym === $nextYm) return true;
                return false;
            })
            ->sortKeys()
            ->values();

        // Unpaid invoices count: only count invoices that are due (due date today or passed) and not paid.
        $unpaidInvoices = $chosenByMonth
            ->filter(function ($inv, $ym) use ($rentStartMonthYm, $currentYm, $dueDay, $today) {
                if ($rentStartMonthYm && $ym < $rentStartMonthYm) return false;
                if ($ym > $currentYm) return false; // don't count future months as unpaid
                if ((string)($inv->status ?? '') === '4' || (int)($inv->status ?? 0) === 4) return false;

                try {
                    $monthStart = \Carbon\Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
                } catch (\Throwable $e) {
                    return false;
                }
                $dueDate = \Carbon\Carbon::create($monthStart->year, $monthStart->month, $dueDay);
                if ((int)$dueDate->month !== (int)$monthStart->month) {
                    $dueDate = $monthStart->copy()->endOfMonth();
                }
                return $dueDate->startOfDay()->lte($today);
            })
            ->sortKeys()
            ->values();

        $unpaidInvoicesCount = $unpaidInvoices->count();
        
        // Calculate income report metrics
        $incomeReport = $this->calculateIncomeReport($id, $boutiqueLocation);
        
        // Get sales data grouped by transfer_code
        $salesByTransfer = [];
        $transfersWithItems = \App\Models\Transfer::with(['items' => function($query) {
            $query->with('stock');
        }])
            ->where('to', $boutiqueLocation)
            ->orderBy('date', 'desc')
            ->get();
        
        foreach ($transfersWithItems as $transfer) {
            // Get all settlements for this boutique (to find sold items)
            $allSettlements = \App\Models\Settlement::where('boutique_id', $id)->get();
            
            // Calculate items sent in this transfer
            $itemsSent = 0;
            $totalAmountSent = 0;
            $transferItemsData = [];
            
            foreach ($transfer->items as $transferItem) {
                $qty = (int)$transferItem->quantity;
                $itemsSent += $qty;
                
                // Get stock for price calculation
                $stock = \App\Models\Stock::where('abaya_code', $transferItem->abaya_code)->first();
                $itemPrice = $stock ? (float)($stock->sales_price ?? 0) : 0;
                $itemAmount = $itemPrice * $qty;
                $totalAmountSent += $itemAmount;
                
                // Store item details for detail popup
                $transferItemsData[] = [
                    'code' => $transferItem->abaya_code,
                    'color' => $transferItem->color_name ?? '',
                    'size' => $transferItem->size_name ?? '',
                    'quantity' => $qty,
                    'price' => $itemPrice
                ];
            }
            
            // Calculate sold amount and profit from settlements
            $soldAmount = 0;
            $profit = 0;
            $totalItemsSold = 0;
            
            // Create a map of transfer items for quick lookup
            $transferItemsMap = [];
            foreach ($transferItemsData as $item) {
                $key = $item['code'] . '|' . ($item['color'] ?? '') . '|' . ($item['size'] ?? '');
                if (!isset($transferItemsMap[$key])) {
                    $transferItemsMap[$key] = $item;
                }
            }
            
            foreach ($allSettlements as $settlement) {
                if ($settlement->items_data) {
                    $itemsData = is_string($settlement->items_data) 
                        ? json_decode($settlement->items_data, true) 
                        : $settlement->items_data;
                    
                    if (is_array($itemsData)) {
                        foreach ($itemsData as $item) {
                            $itemCode = $item['code'] ?? '';
                            $itemColor = $item['color'] ?? '';
                            $itemSize = $item['size'] ?? '';
                            $key = $itemCode . '|' . $itemColor . '|' . $itemSize;
                            
                            // Check if this item matches any item in the transfer
                            if (isset($transferItemsMap[$key])) {
                                $soldQty = (int)($item['sold'] ?? 0);
                                if ($soldQty > 0) {
                                    $totalItemsSold += $soldQty;
                                    $itemPrice = (float)($item['price'] ?? $transferItemsMap[$key]['price']);
                                    $soldAmount += $itemPrice * $soldQty;
                                    
                                    // Calculate profit: (sales_price - cost_price - tailor_charges) * sold_quantity
                                    $stock = \App\Models\Stock::where('abaya_code', $itemCode)->first();
                                    if ($stock) {
                                        $salesPrice = (float)($stock->sales_price ?? 0);
                                        $costPrice = (float)($stock->cost_price ?? 0);
                                        $tailorCharges = (float)($stock->tailor_charges ?? 0);
                                        $itemProfit = ($salesPrice - $costPrice - $tailorCharges) * $soldQty;
                                        $profit += $itemProfit;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Determine status: compare total items sent vs total items sold
            $status = 'not_paid';
            if ($totalItemsSold == $itemsSent && $itemsSent > 0) {
                $status = 'fully_paid';
            } elseif ($totalItemsSold > 0 && $totalItemsSold < $itemsSent) {
                $status = 'partially_paid';
            }
            
            $salesByTransfer[] = [
                'transfer_code' => $transfer->transfer_code,
                'transfer_id' => $transfer->id,
                'date' => $transfer->date->format('Y-m-d'),
                'items_sent' => $itemsSent,
                'total_amount_sent' => $totalAmountSent,
                'sold_amount' => $soldAmount,
                'profit' => $profit,
                'status' => $status,
                'items' => $transferItemsData
            ];
        }
        
        // Get shipments data (transfers) for shipments tab
        $shipmentsData = [];
        foreach ($transfersWithItems as $transfer) {
            $itemsCount = 0;
            $totalAmount = 0;
            $shipmentItems = [];
            
            foreach ($transfer->items as $transferItem) {
                $qty = (int)$transferItem->quantity;
                $itemsCount += $qty;
                
                // Get stock for price calculation
                $stock = \App\Models\Stock::where('abaya_code', $transferItem->abaya_code)->first();
                $itemPrice = $stock ? (float)($stock->sales_price ?? 0) : 0;
                $itemAmount = $itemPrice * $qty;
                $totalAmount += $itemAmount;
                
                // Store item details for detail popup
                $shipmentItems[] = [
                    'code' => $transferItem->abaya_code,
                    'color' => $transferItem->color_name ?? '',
                    'size' => $transferItem->size_name ?? '',
                    'quantity' => $qty,
                    'price' => $itemPrice
                ];
            }
            
            // Get "from" channel/boutique name
            $fromLocation = $transfer->from ?? 'main';
            $fromName = trans('messages.main_warehouse', [], session('locale'));
            if ($fromLocation !== 'main') {
                if (strpos($fromLocation, 'channel-') === 0) {
                    $channelId = (int)explode('-', $fromLocation)[1];
                    $channel = \App\Models\Channel::find($channelId);
                    if ($channel) {
                        $fromName = session('locale') === 'ar' 
                            ? ($channel->channel_name_ar ?? $channel->channel_name_en ?? $fromLocation)
                            : ($channel->channel_name_en ?? $channel->channel_name_ar ?? $fromLocation);
                    } else {
                        $fromName = $fromLocation;
                    }
                } elseif (strpos($fromLocation, 'boutique-') === 0) {
                    $boutiqueId = (int)explode('-', $fromLocation)[1];
                    $boutique = \App\Models\Boutique::find($boutiqueId);
                    if ($boutique) {
                        $fromName = $boutique->boutique_name ?? $fromLocation;
                    } else {
                        $fromName = $fromLocation;
                    }
                } else {
                    $fromName = $fromLocation;
                }
            }
            
            $shipmentsData[] = [
                'transfer_code' => $transfer->transfer_code,
                'transfer_id' => $transfer->id,
                'date' => $transfer->date->format('Y-m-d'),
                'total_items' => $itemsCount,
                'total_amount' => $totalAmount,
                'from_channel' => $fromName,
                'items' => $shipmentItems
            ];
        }
        
        return view('boutique::boutique_profile', compact(
            'boutique',
            'totalAbayas',
            'numberOfShipments',
            'unpaidRentInvoice',
            'unpaidInvoices',
            'unpaidInvoicesCount',
            'allInvoices',
            'totalSales',
            'bestSellingColor',
            'mostRequestedSize',
            'monthlyProfit',
            'last6Months',
            'monthNames',
            'salesData',
            'salesByTransfer',
            'shipmentsData',
            'incomeReport'
        ));
    }


public function getboutiques() {
    $boutiques = Boutique::orderBy('id', 'DESC')->paginate(10);
    
    // Add unpaid months info to each boutique
    foreach ($boutiques->items() as $boutique) {
        $today = Carbon::now()->startOfDay();
        $rentStartDate = $boutique->rent_date ? Carbon::parse($boutique->rent_date)->startOfDay() : null;
        $rentStartMonthYm = $rentStartDate ? $rentStartDate->copy()->startOfMonth()->format('Y-m') : null;
        $dueDay = $rentStartDate ? (int)$rentStartDate->day : 1;

        // Some records were stored as "m-Y" and others as "Y-m".
        // Treat a month as PAID if any invoice for that month has status 4.
        $allInvoices = BoutiqueInvo::where('boutique_id', (string)$boutique->id)
            ->get(['month', 'status']);

        $months = []; // ym => ['paid' => bool]
        foreach ($allInvoices as $inv) {
            $ym = $this->normalizeInvoiceMonthToYm($inv->month);
            if (!$ym) continue;

            // Never consider months before rent start month
            if ($rentStartMonthYm && $ym < $rentStartMonthYm) {
                continue;
            }

            if (!isset($months[$ym])) {
                $months[$ym] = ['paid' => false];
            }
            if ((string)$inv->status === '4' || (int)$inv->status === 4) {
                $months[$ym]['paid'] = true;
            }
        }

        // Only mark as "unpaid" if the invoice is DUE (due date is today or passed).
        // Future months (e.g., next month) should not make the list show unpaid early.
        $unpaidMonths = [];
        foreach ($months as $ym => $info) {
            if ($info['paid']) {
                continue;
            }

            try {
                $monthStart = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
            } catch (\Throwable $e) {
                continue;
            }

            // Due date = same day-of-month as boutique rent start (overflow safe)
            $dueDate = Carbon::create($monthStart->year, $monthStart->month, $dueDay);
            if ((int)$dueDate->month !== (int)$monthStart->month) {
                $dueDate = $monthStart->copy()->endOfMonth();
            }
            $dueDate = $dueDate->startOfDay();

            if ($dueDate->lte($today)) {
                $unpaidMonths[] = $this->formatYmToMY($ym);
            }
        }

        // Sort by date
        usort($unpaidMonths, function ($a, $b) {
            $pa = $this->normalizeInvoiceMonthToYm($a) ?? $a;
            $pb = $this->normalizeInvoiceMonthToYm($b) ?? $b;
            return strcmp($pa, $pb);
        });

        $boutique->unpaid_months = $unpaidMonths;
        $boutique->unpaid_count = count($unpaidMonths);
    }
    
    return $boutiques;
}

public function show($id) {
    $boutique = Boutique::findOrFail($id);
    return response()->json($boutique);
}

  public function add_boutique(Request $request)
    {
        $boutique = new Boutique();

        $boutique->boutique_name = $request->boutique_name;
        $boutique->shelf_no = $request->shelf_no;
        $boutique->monthly_rent = $request->monthly_rent;
        $boutique->rent_date = $request->rent_date;
        $boutique->status = $request->status; // Active/Inactive status
        $boutique->rent_invoice_status = '5'; // Always set to unpaid (5) when adding new boutique
        $boutique->boutique_address = $request->boutique_address;
        $boutique->added_by_by = 'system';
        $boutique->user_id = 1;

        $boutique->save();
        
        // Create invoice record in boutique_invos table
        if ($boutique->rent_date && $boutique->monthly_rent) {
            $rentDate = \Carbon\Carbon::parse($boutique->rent_date);
            $month = $rentDate->format('m-Y'); // Format: MM-YYYY (e.g., 02-2025)
            
            $invoice = new BoutiqueInvo();
            $invoice->boutique_id = (string)$boutique->id;
            $invoice->boutique_name = $boutique->boutique_name;
            $invoice->month = $month;
            $invoice->payment_date = $boutique->rent_date;
            $invoice->status = '5'; // Status 4 = Paid
            $invoice->total_amount = $boutique->monthly_rent;
            $invoice->added_by = 'system';
            $invoice->user_id = 1;
            $invoice->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Boutique saved successfully!'
        ]);
    }

    public function destroy($id)
{

    $boutique = Boutique::find($id);

    if (!$boutique) {
        return response()->json([
            'success' => false,
            'message' => __('messages.not_found')
        ]);
    }

    try {
        $boutique->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.boutique_deleted')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => __('messages.delete_error')
        ]);
    }
}

    
    public function boutique_list(Request $request)
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(11, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        // Generate missing invoice records for all boutiques
        $this->generateMissingInvoices();
        
        return view('boutique::view_boutique');
    }
    
    /**
     * Generate missing invoice records for all boutiques
     * Creates invoice records from rent_date to current month
     */
    private function generateMissingInvoices()
    {
        $currentDate = now();
        $currentMonth = $currentDate->format('m-Y'); // Format: MM-YYYY
        
        // Get all boutiques with rent_date
        $boutiques = Boutique::whereNotNull('rent_date')
            ->whereNotNull('monthly_rent')
            ->get();
        
        foreach ($boutiques as $boutique) {
            if (!$boutique->rent_date || !$boutique->monthly_rent) {
                continue;
            }
            
            $rentDate = \Carbon\Carbon::parse($boutique->rent_date);
            $rentMonth = $rentDate->format('m-Y'); // Format: MM-YYYY
            
            // Generate all months from rent_date to current month
            $startDate = $rentDate->copy()->startOfMonth();
            $endDate = $currentDate->copy()->startOfMonth();
            
            // Loop through each month
            $currentMonthDate = $startDate->copy();
            while ($currentMonthDate->lte($endDate)) {
                $monthKey = $currentMonthDate->format('m-Y'); // Format: MM-YYYY
                
                // Check if invoice already exists for this boutique and month
                $existingInvoice = BoutiqueInvo::where('boutique_id', (string)$boutique->id)
                    ->where('month', $monthKey)
                    ->first();
                
                if (!$existingInvoice) {
                    // Create new invoice record
                    $invoice = new BoutiqueInvo();
                    $invoice->boutique_id = (string)$boutique->id;
                    $invoice->boutique_name = $boutique->boutique_name;
                    $invoice->month = $monthKey;
                    
                    // If this is the rent_date month, set status to 4 (paid) and use rent_date as payment_date
                    // Otherwise, set status to 5 (unpaid) and payment_date to null
                    if ($monthKey === $rentMonth) {
                        $invoice->status = '4'; // Paid
                        $invoice->payment_date = $boutique->rent_date;
                    } else {
                        $invoice->status = '5'; // Unpaid
                        $invoice->payment_date = null;
                    }
                    
                    $invoice->total_amount = $boutique->monthly_rent;
                    $invoice->added_by = 'system';
                    $invoice->user_id = 1;
                    $invoice->save();
                }
                
                // Move to next month
                $currentMonthDate->addMonth();
            }
        }
    }

      public function edit_boutique($id)
    {
       
        $boutique = Boutique::find($id);

    return view('boutique::edit_boutique', compact('boutique'));
    }


  public function update_boutique(Request $request)
{
    $boutique_id = $request->boutique_id;
    $boutique = Boutique::find($boutique_id);

    $boutique->boutique_name = $request->boutique_name;
    $boutique->shelf_no = $request->shelf_no;
    $boutique->monthly_rent = $request->monthly_rent;
    $boutique->rent_date = $request->rent_date;
    $boutique->status = $request->status;
    $boutique->boutique_address = $request->boutique_address;
    $boutique->updated_by = 'system_update';

    $boutique->save();

    return response()->json([
        'success' => true,
        'message' => $request->boutique_id 
            ? __('messages.update_boutique') 
            : __('messages.add_boutique'),
        'boutique' => $boutique // ✅ include this
    ]);
}

public function get_boutique_invoices(Request $request)
{
    $boutique_id = $request->input('boutique_id');
    
    if (!$boutique_id) {
        return response()->json([
            'success' => false,
            'message' => 'Boutique ID is required'
        ], 400);
    }
    
    $boutique = Boutique::find($boutique_id);
    
    if (!$boutique) {
        return response()->json([
            'success' => false,
            'message' => __('messages.not_found')
        ], 404);
    }

    // Popup requirement:
    // - Always show CURRENT month invoice (paid or unpaid)
    // - Show NEXT month invoice only within 10 days before its due date
    // - Show ALL unpaid invoices (up to current month only; do not show future unpaid months)
    // - Show only LAST 4 paid invoices
    // - Never show months before rent start month

    $today = Carbon::now()->startOfDay();
    $rentStartDate = Carbon::parse($boutique->rent_date)->startOfDay();
    $rentStartMonth = $rentStartDate->copy()->startOfMonth();
    $dueDay = (int)$rentStartDate->day;

    $currentMonth = Carbon::now()->startOfMonth();
    if ($currentMonth->lt($rentStartMonth)) {
        $currentMonth = $rentStartMonth->copy();
    }
    $currentYm = $currentMonth->format('Y-m');
    $nextMonth = $currentMonth->copy()->addMonthNoOverflow();
    $nextYm = $nextMonth->format('Y-m');

    // Next due date for NEXT month (same day-of-month, overflow-safe)
    $nextDueDate = Carbon::create($nextMonth->year, $nextMonth->month, $dueDay);
    if ((int)$nextDueDate->month !== (int)$nextMonth->month) {
        $nextDueDate = $nextMonth->copy()->endOfMonth();
    }
    $daysUntilNextDue = $today->diffInDays($nextDueDate->copy()->startOfDay(), false);
    $showNextMonth = ($daysUntilNextDue >= 0 && $daysUntilNextDue <= 10);

    // Load all invoices for boutique, group by normalized month
    $rawInvoices = BoutiqueInvo::where('boutique_id', (string)$boutique_id)
        ->orderBy('id', 'desc')
        ->get();

    $groups = []; // ym => Collection
    foreach ($rawInvoices as $inv) {
        $ym = $this->normalizeInvoiceMonthToYm($inv->month);
        if (!$ym) continue;
        // Never show months before rent start month
        if ($ym < $rentStartMonth->format('Y-m')) continue;
        if (!isset($groups[$ym])) $groups[$ym] = collect();
        $groups[$ym]->push($inv);
    }

    $pickBestInvoiceForMonth = function ($list) {
        if (!$list || $list->count() === 0) return null;
        return $list->first(function ($x) {
            return (string)($x->status ?? '') === '4' || (int)($x->status ?? 0) === 4;
        }) ?? $list->first(function ($x) {
            return (string)($x->status ?? '') === '5' || (int)($x->status ?? 0) === 5;
        }) ?? $list->first();
    };

    // Ensure current month invoice exists
    if (!isset($groups[$currentYm]) || $groups[$currentYm]->count() === 0) {
        $invoice = new BoutiqueInvo();
        $invoice->boutique_id = (string)$boutique_id;
        $invoice->boutique_name = $boutique->boutique_name;
        $invoice->month = $this->formatYmToMY($currentYm);
        $invoice->payment_date = null;
        $invoice->status = '5';
        $invoice->total_amount = $boutique->monthly_rent ?? 0;
        $invoice->added_by = 'system';
        $invoice->user_id = 1;
        $invoice->save();
        $groups[$currentYm] = collect([$invoice]);
    }

    // Ensure next month invoice exists only if it should be shown
    if ($showNextMonth && (!isset($groups[$nextYm]) || $groups[$nextYm]->count() === 0)) {
        $invoice = new BoutiqueInvo();
        $invoice->boutique_id = (string)$boutique_id;
        $invoice->boutique_name = $boutique->boutique_name;
        $invoice->month = $this->formatYmToMY($nextYm);
        $invoice->payment_date = null;
        $invoice->status = '5';
        $invoice->total_amount = $boutique->monthly_rent ?? 0;
        $invoice->added_by = 'system';
        $invoice->user_id = 1;
        $invoice->save();
        $groups[$nextYm] = collect([$invoice]);
    }

    // Unpaid invoices: show all unpaid months up to current month (do not show future unpaid)
    $unpaidMonths = [];
    foreach ($groups as $ym => $list) {
        if ($ym > $currentYm) continue;
        $hasPaid = $list->first(function ($x) {
            return (string)($x->status ?? '') === '4' || (int)($x->status ?? 0) === 4;
        }) ? true : false;
        if ($hasPaid) continue;
        $hasUnpaid = $list->first(function ($x) {
            return (string)($x->status ?? '') === '5' || (int)($x->status ?? 0) === 5;
        }) ? true : false;
        if ($hasUnpaid) {
            $unpaidMonths[] = $ym;
        }
    }
    sort($unpaidMonths);

    // Paid history: last 4 paid invoices for this boutique
    $paidCandidates = collect();
    foreach ($groups as $ym => $list) {
        $paidList = $list->filter(function ($x) {
            return (string)($x->status ?? '') === '4' || (int)($x->status ?? 0) === 4;
        });
        if ($paidList->count() === 0) continue;

        $bestPaid = $paidList->sortByDesc(function ($x) {
            return $x->payment_date ? strtotime($x->payment_date) : 0;
        })->sortByDesc('id')->first();
        if ($bestPaid) {
            $paidCandidates->push([
                'ym' => $ym,
                'inv' => $bestPaid,
                'paid_ts' => $bestPaid->payment_date ? strtotime($bestPaid->payment_date) : 0,
            ]);
        }
    }
    $paidHistoryMonths = $paidCandidates
        ->sortByDesc('paid_ts')
        ->take(4)
        ->pluck('ym')
        ->values()
        ->all();

    // Build final months list in required order:
    // 1) all unpaid (<= current month)
    // 2) current month (always)
    // 3) next month (only in 10-day window)
    // 4) last 4 paid
    $finalMonths = [];
    $addMonth = function ($ym) use (&$finalMonths) {
        if (!in_array($ym, $finalMonths, true)) {
            $finalMonths[] = $ym;
        }
    };
    foreach ($unpaidMonths as $ym) $addMonth($ym);
    $addMonth($currentYm);
    if ($showNextMonth) $addMonth($nextYm);
    foreach ($paidHistoryMonths as $ym) $addMonth($ym);

    $invoices = collect();
    foreach ($finalMonths as $ym) {
        $list = $groups[$ym] ?? collect();
        $chosen = $pickBestInvoiceForMonth($list);
        if (!$chosen) continue;
        $invoices->push([
            'id' => $chosen->id,
            'month' => $this->formatYmToMY($ym),
            'month_ym' => $ym,
            'payment_date' => $chosen->payment_date,
            'status' => (string)($chosen->status ?? ''),
            'total_amount' => $chosen->total_amount,
        ]);
    }
    
    return response()->json([
        'success' => true,
        'boutique' => [
            'id' => $boutique->id,
            'name' => $boutique->boutique_name,
            'monthly_rent' => $boutique->monthly_rent
        ],
        'invoices' => $invoices
    ]);
}

public function update_invoice_payment(Request $request)
{
    // Handle both single invoice and array of invoices
    $invoices = $request->input('invoices');
    
    if ($invoices && is_array($invoices)) {
        // Handle array of invoices
        foreach ($invoices as $invoiceData) {
            $invoiceId = $invoiceData['id'] ?? null;
            $amount = $invoiceData['total_amount'] ?? null;
            $paymentDate = $invoiceData['payment_date'] ?? null;
            
            if (!$invoiceId) {
                continue;
            }
            
            $invoice = BoutiqueInvo::find($invoiceId);
            
            if (!$invoice) {
                continue;
            }
            
            // Update invoice
            if ($amount !== null) {
                $invoice->total_amount = $amount;
            }
            
            if ($paymentDate !== null) {
                $invoice->payment_date = $paymentDate;
            }
            
            // Set status to paid (4) when payment is made
            $invoice->status = '4';
            $invoice->save();
        }
        
        return response()->json([
            'success' => true,
            'message' => __('messages.payments_updated_successfully')
        ]);
    }
    
    // Handle single invoice (backward compatibility)
    $invoice_id = $request->input('invoice_id');
    $amount = $request->input('amount');
    $payment_date = $request->input('payment_date');
    
    if (!$invoice_id) {
        return response()->json([
            'success' => false,
            'message' => 'Invoice ID is required'
        ], 400);
    }
    
    $invoice = BoutiqueInvo::find($invoice_id);
    
    if (!$invoice) {
        return response()->json([
            'success' => false,
            'message' => __('messages.not_found')
        ], 404);
    }
    
    // Update invoice
    if ($amount !== null) {
        $invoice->total_amount = $amount;
    }
    if ($payment_date !== null) {
        $invoice->payment_date = $payment_date;
    }
    
    // Set status to paid (4) when payment is made
    $invoice->status = '4';
    $invoice->save();
    
    return response()->json([
        'success' => true,
        'message' => __('messages.payment_updated_successfully'),
        'invoice' => $invoice
    ]);
}

public function update_rent_invoice_status(Request $request)
{
    $boutique_id = $request->boutique_id;
    $status = $request->status; // 4 for paid, 5 for unpaid
    
    $boutique = Boutique::find($boutique_id);
    
    if (!$boutique) {
        return response()->json([
            'success' => false,
            'message' => __('messages.not_found')
        ]);
    }
    
    $boutique->rent_invoice_status = $status;
    $boutique->save();
    
    return response()->json([
        'success' => true,
        'message' => __('messages.rent_invoice_status_updated'),
        'boutique' => $boutique
    ]);
}

/**
 * Calculate income report for boutique
 */
private function calculateIncomeReport($boutiqueId, $boutiqueLocation)
{
    // Get all transfers TO this boutique
    $transfersTo = \App\Models\Transfer::with('items.stock')
        ->where('to', $boutiqueLocation)
        ->get();
    
    // Get all transfers FROM this boutique (pulled items)
    $transfersFrom = \App\Models\Transfer::with('items.stock')
        ->where('from', $boutiqueLocation)
        ->get();
    
    // Calculate total items sent
    $totalItemsSent = 0;
    $totalPriceSent = 0;
    
    foreach ($transfersTo as $transfer) {
        foreach ($transfer->items as $transferItem) {
            $qty = (int)$transferItem->quantity;
            $totalItemsSent += $qty;
            
            // Get stock for price calculation
            $stock = \App\Models\Stock::where('abaya_code', $transferItem->abaya_code)->first();
            if ($stock && $stock->sales_price) {
                $totalPriceSent += (float)$stock->sales_price * $qty;
            }
        }
    }
    
    // Calculate total items pulled
    $totalItemsPulled = 0;
    foreach ($transfersFrom as $transfer) {
        $totalItemsPulled += (int)$transfer->quantity;
    }
    
    // Calculate total sellable (from transfers table where to = boutique)
    $totalSellable = \App\Models\Transfer::where('to', $boutiqueLocation)
        ->sum('sellable');
    
    // Calculate total sold and total profit from settlements
    $totalSold = 0;
    $totalProfit = 0;
    
    $allSettlements = \App\Models\Settlement::where('boutique_id', $boutiqueId)->get();
    
    foreach ($allSettlements as $settlement) {
        if ($settlement->items_data) {
            $itemsData = is_string($settlement->items_data) 
                ? json_decode($settlement->items_data, true) 
                : $settlement->items_data;
            
            if (is_array($itemsData)) {
                foreach ($itemsData as $item) {
                    $soldQty = (int)($item['sold'] ?? 0);
                    if ($soldQty > 0) {
                        $totalSold += $soldQty;
                        
                        // Calculate profit: (sales_price - cost_price - tailor_charges) * sold_quantity
                        $stock = \App\Models\Stock::where('abaya_code', $item['code'] ?? '')->first();
                        if ($stock) {
                            $salesPrice = (float)($stock->sales_price ?? 0);
                            $costPrice = (float)($stock->cost_price ?? 0);
                            $tailorCharges = (float)($stock->tailor_charges ?? 0);
                            $itemProfit = ($salesPrice - $costPrice - $tailorCharges) * $soldQty;
                            $totalProfit += $itemProfit;
                        }
                    }
                }
            }
        }
    }
    
    return [
        'total_items_sent' => $totalItemsSent,
        'total_items_pulled' => $totalItemsPulled,
        'total_sellable' => max(0, $totalSellable),
        'total_sold' => $totalSold,
        'total_profit' => $totalProfit,
        'total_price_sent' => $totalPriceSent
    ];
}

}
