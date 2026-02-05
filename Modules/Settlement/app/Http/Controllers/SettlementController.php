<?php

namespace Modules\Settlement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Models\Channel;
use App\Models\ColorSize;
use App\Models\Settlement;
use App\Models\Stock;
use App\Models\StockColor;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class SettlementController extends Controller
{
    /**
     * Show settlement page
     */
    public function settlement()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];
        if (!in_array(11, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        return view('settlement::settlement');
    }

    /**
     * Get list of boutiques for settlement dropdown
     */
    public function get_boutiques_list()
    {
        $boutiques = Boutique::select('id', 'boutique_name')
            ->orderBy('boutique_name', 'asc')
            ->get()
            ->map(function ($boutique) {
                return [
                    'id' => $boutique->id,
                    'name' => $boutique->boutique_name,
                ];
            });

        return response()->json($boutiques);
    }

    /**
     * Get settlement data (transfers sent/pulled for boutique in date range)
     */
    public function get_settlement_data(Request $request)
    {
        $locale = session('locale');
        $boutiqueId = $request->input('boutique_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$boutiqueId || !$dateFrom || !$dateTo) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        $boutiqueLocation = 'boutique-' . $boutiqueId;

        $sentTransfers = Transfer::with('items')
            ->where('to', $boutiqueLocation)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();

        $pulledTransfers = Transfer::with('items')
            ->where('from', $boutiqueLocation)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();

        $sentData = [];
        foreach ($sentTransfers as $transfer) {
            foreach ($transfer->items as $item) {
                $key = $item->abaya_code . '|' . ($item->size_name ?? '') . '|' . ($item->color_name ?? '');
                if (!isset($sentData[$key])) {
                    $sentData[$key] = [
                        'code' => $item->abaya_code,
                        'size' => $item->size_name,
                        'color' => $item->color_name,
                        'color_id' => $item->color_id,
                        'size_id' => $item->size_id,
                        'quantity' => 0,
                        'sellable' => 0,
                        'price' => 0,
                        'color_code' => '#000000',
                    ];
                }
                $sentData[$key]['quantity'] += (int) $item->quantity;
                $sentData[$key]['sellable'] += (int) $item->sellable;
            }
        }

        $pulledData = [];
        foreach ($pulledTransfers as $transfer) {
            foreach ($transfer->items as $item) {
                $key = $item->abaya_code . '|' . ($item->size_name ?? '') . '|' . ($item->color_name ?? '');
                if (!isset($pulledData[$key])) {
                    $pulledData[$key] = [
                        'code' => $item->abaya_code,
                        'size' => $item->size_name,
                        'color' => $item->color_name,
                        'quantity' => 0,
                    ];
                }
                $pulledData[$key]['quantity'] += (int) $item->quantity;
            }
        }

        $codes = array_unique(array_column($sentData, 'code'));
        if (empty($codes)) {
            return response()->json([]);
        }

        $stocks = Stock::whereIn('abaya_code', $codes)->get()->keyBy('abaya_code');
        $stockIds = $stocks->pluck('id')->toArray();
        $stockColors = StockColor::with('color')
            ->whereIn('stock_id', $stockIds)
            ->get()
            ->groupBy('stock_id');
        $colorSizes = ColorSize::with('color', 'size')
            ->whereIn('stock_id', $stockIds)
            ->get()
            ->groupBy('stock_id');

        $result = [];
        foreach ($sentData as $key => $sent) {
            $pulledQty = $pulledData[$key]['quantity'] ?? 0;
            $sellable = max(0, $sent['sellable'] - $pulledQty);
            $stock = $stocks->get($sent['code']);
            $price = $stock ? (float) ($stock->sales_price ?? 0) : 0;

            $colorCode = '#000000';
            if ($stock && $sent['color']) {
                if ($sent['size']) {
                    $combinations = $colorSizes->get($stock->id);
                    if ($combinations) {
                        foreach ($combinations as $cs) {
                            if ($cs->color && ($cs->color->color_name_ar === $sent['color'] || $cs->color->color_name_en === $sent['color'])) {
                                if ($cs->size && ($cs->size->size_name_ar === $sent['size'] || $cs->size->size_name_en === $sent['size'])) {
                                    $colorCode = $cs->color->color_code ?? '#000000';
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    $cList = $stockColors->get($stock->id);
                    if ($cList) {
                        foreach ($cList as $sc) {
                            if ($sc->color && ($sc->color->color_name_ar === $sent['color'] || $sc->color->color_name_en === $sent['color'])) {
                                $colorCode = $sc->color->color_code ?? '#000000';
                                break;
                            }
                        }
                    }
                }
            }

            $result[] = [
                'uid' => $key,
                'code' => $sent['code'],
                'color' => $sent['color'],
                'color_id' => $sent['color_id'],
                'color_code' => $colorCode,
                'size' => $sent['size'],
                'size_id' => $sent['size_id'],
                'sent' => $sent['quantity'],
                'pulled' => $pulledQty,
                'sellable' => $sellable,
                'price' => $price,
                'sold' => 0,
                'diff' => 0,
                'total' => 0,
            ];
        }

        return response()->json($result);
    }

    /**
     * Get transfer details for a specific item
     */
    public function get_settlement_transfer_details(Request $request)
    {
        $locale = session('locale');
        $boutiqueId = $request->input('boutique_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $code = $request->input('code');
        $color = $request->input('color');
        $size = $request->input('size');

        if (!$boutiqueId || !$dateFrom || !$dateTo || !$code) {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        $boutiqueLocation = 'boutique-' . $boutiqueId;

        $getLocationName = function ($locationId) use ($locale) {
            if ($locationId === 'main') {
                return trans('messages.main_warehouse', [], $locale);
            }
            if (strpos($locationId, 'boutique-') === 0) {
                $id = (int) explode('-', $locationId)[1];
                $boutique = Boutique::find($id);
                return $boutique ? $boutique->boutique_name : $locationId;
            }
            if (strpos($locationId, 'channel-') === 0) {
                $id = (int) explode('-', $locationId)[1];
                $channel = Channel::find($id);
                if ($channel) {
                    return $locale == 'ar' ? $channel->channel_name_ar : $channel->channel_name_en;
                }
            }
            return $locationId;
        };

        $sentTransfers = Transfer::with('items')
            ->where('to', $boutiqueLocation)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();

        $pulledTransfers = Transfer::with('items')
            ->where('from', $boutiqueLocation)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();

        $movements = [];
        foreach ($sentTransfers as $transfer) {
            foreach ($transfer->items as $item) {
                if ($item->abaya_code === $code) {
                    $colorMatch = (!$color && !$item->color_name) || ($item->color_name === $color);
                    $sizeMatch = (!$size && !$item->size_name) || ($item->size_name === $size);
                    if ($colorMatch && $sizeMatch) {
                        $movements[] = [
                            'id' => $transfer->id . '-' . $item->id,
                            'date' => $transfer->date->format('Y-m-d'),
                            'type' => trans('messages.sent', [], $locale),
                            'from' => $getLocationName($transfer->from),
                            'to' => $getLocationName($transfer->to),
                            'qty' => $item->quantity,
                            'transfer_code' => $transfer->transfer_code,
                        ];
                    }
                }
            }
        }

        foreach ($pulledTransfers as $transfer) {
            foreach ($transfer->items as $item) {
                if ($item->abaya_code === $code) {
                    $colorMatch = (!$color && !$item->color_name) || ($item->color_name === $color);
                    $sizeMatch = (!$size && !$item->size_name) || ($item->size_name === $size);
                    if ($colorMatch && $sizeMatch) {
                        $movements[] = [
                            'id' => $transfer->id . '-' . $item->id,
                            'date' => $transfer->date->format('Y-m-d'),
                            'type' => trans('messages.pulled', [], $locale),
                            'from' => $getLocationName($transfer->from),
                            'to' => $getLocationName($transfer->to),
                            'qty' => $item->quantity,
                            'transfer_code' => $transfer->transfer_code,
                        ];
                    }
                }
            }
        }

        usort($movements, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return response()->json($movements);
    }

    /**
     * Save settlement record
     */
    public function save_settlement(Request $request)
    {
        $user_id = Auth::guard('tenant')->id();
        $user = Auth::guard('tenant')->user();
        $user_name = $user ? ($user->user_name ?? $user->name ?? 'system') : 'system';
        $locale = session('locale');

        $boutiqueId = $request->input('boutique_id');
        $boutiqueName = $request->input('boutique_name');
        $month = $request->input('month');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $numberOfItems = $request->input('number_of_items', 0);
        $totalSales = $request->input('total_sales', 0);
        $totalDifference = $request->input('total_difference', 0);
        $itemsData = $request->input('items_data', []);
        $notes = $request->input('notes', '');

        if (is_string($itemsData)) {
            $itemsData = json_decode($itemsData, true) ?? [];
        }

        foreach ($itemsData as $item) {
            $abayaCode = $item['code'] ?? '';
            $colorId = $item['color_id'] ?? null;
            $sizeId = $item['size_id'] ?? null;
            TransferItem::where('abaya_code', $abayaCode)
                ->where('color_id', $colorId)
                ->where('size_id', $sizeId)
                ->update([
                    'sellable' => max(0, ($item['sellable'] ?? 0) - ($item['sold'] ?? 0)),
                ]);
        }

        $settlementCode = 'STL-' . $month . '-' . str_pad((Settlement::where('month', $month)->count() + 1), 2, '0', STR_PAD_LEFT);

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $folderPath = public_path('images/settlements');
            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $fileName = time() . '_' . $attachmentName;
            $file->move($folderPath, $fileName);
            $attachmentPath = 'images/settlements/' . $fileName;
        }

        $settlement = new Settlement();
        $settlement->settlement_code = $settlementCode;
        $settlement->boutique_id = $boutiqueId;
        $settlement->boutique_name = $boutiqueName;
        $settlement->month = $month;
        $settlement->date_from = $dateFrom;
        $settlement->date_to = $dateTo;
        $settlement->number_of_items = $numberOfItems;
        $settlement->total_sales = $totalSales;
        $settlement->total_difference = $totalDifference;
        $settlement->attachment_path = $attachmentPath;
        $settlement->attachment_name = $attachmentName;
        $settlement->notes = $notes;
        $settlement->items_data = $itemsData;
        $settlement->added_by = $user_name;
        $settlement->user_id = $user_id;
        $settlement->save();

        $boutiqueLocation = 'boutique-' . $boutiqueId;
        $transfers = Transfer::with('items')
            ->where('to', $boutiqueLocation)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($itemsData as $item) {
            $code = $item['code'] ?? '';
            $size = $item['size'] ?? '';
            $color = $item['color'] ?? '';
            $colorId = $item['color_id'] ?? null;
            $sizeId = $item['size_id'] ?? null;
            $soldQty = isset($item['sold']) ? (int) $item['sold'] : 0;

            if ($soldQty <= 0) {
                continue;
            }

            $remainingSold = $soldQty;
            foreach ($transfers as $transfer) {
                if ($remainingSold <= 0) {
                    break;
                }

                $transferTotalQty = 0;
                $matchingItemQty = 0;
                foreach ($transfer->items as $transferItem) {
                    $transferTotalQty += (int) $transferItem->quantity;
                    $codeMatch = $transferItem->abaya_code === $code;
                    $sizeMatch = ($transferItem->size_name ?? '') === $size;
                    $colorMatch = ($transferItem->color_name ?? '') === $color;
                    if ($colorId !== null) {
                        $colorMatch = $colorMatch && ($transferItem->color_id == $colorId);
                    }
                    if ($sizeId !== null) {
                        $sizeMatch = $sizeMatch && ($transferItem->size_id == $sizeId);
                    }
                    if ($codeMatch && $sizeMatch && $colorMatch) {
                        $matchingItemQty += (int) $transferItem->quantity;
                    }
                }

                if ($matchingItemQty > 0 && $transferTotalQty > 0) {
                    $currentSellable = (int) ($transfer->sellable ?? $transfer->quantity);
                    $proportionalSellable = ($currentSellable * $matchingItemQty) / $transferTotalQty;
                    $decreaseAmount = min($remainingSold, (int) round($proportionalSellable));

                    if ($decreaseAmount > 0) {
                        $transfer->sellable = max(0, $currentSellable - $decreaseAmount);
                        $transfer->save();
                        $remainingSold -= $decreaseAmount;
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => trans('messages.settlement_saved_successfully', [], $locale),
            'settlement_code' => $settlementCode,
            'id' => $settlement->id,
        ]);
    }

    /**
     * Get settlement history with pagination
     */
    public function get_settlement_history(Request $request)
    {
        $locale = session('locale');
        $search = $request->input('search', '');
        $month = $request->input('month', '');
        $page = $request->input('page', 1);

        $query = Settlement::orderBy('month', 'desc')->orderBy('id', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('settlement_code', 'like', '%' . $search . '%')
                    ->orWhere('boutique_name', 'like', '%' . $search . '%');
            });
        }

        if ($month) {
            $query->where('month', $month);
        }

        $settlements = $query->paginate(10, ['*'], 'page', $page);

        $history = [];
        foreach ($settlements as $settlement) {
            $history[] = [
                'no' => $settlement->settlement_code,
                'month' => $settlement->month,
                'boutique' => $settlement->boutique_id,
                'boutique_name' => $settlement->boutique_name,
                'items' => $settlement->number_of_items,
                'amount' => (float) $settlement->total_sales,
                'diff' => $settlement->total_difference,
                'attachment_path' => $settlement->attachment_path,
                'attachment_name' => $settlement->attachment_name,
                'date_from' => $settlement->date_from ? $settlement->date_from->format('Y-m-d') : null,
                'date_to' => $settlement->date_to ? $settlement->date_to->format('Y-m-d') : null,
            ];
        }

        return response()->json([
            'data' => $history,
            'current_page' => $settlements->currentPage(),
            'last_page' => $settlements->lastPage(),
            'total' => $settlements->total(),
            'per_page' => $settlements->perPage(),
            'from' => $settlements->firstItem(),
            'to' => $settlements->lastItem(),
        ]);
    }

    /**
     * Get settlement details by code
     */
    public function get_settlement_details(Request $request)
    {
        $settlementCode = $request->input('settlement_code');

        if (!$settlementCode) {
            return response()->json(['error' => 'Settlement code is required'], 400);
        }

        $settlement = Settlement::where('settlement_code', $settlementCode)->first();

        if (!$settlement) {
            return response()->json(['error' => 'Settlement not found'], 404);
        }

        $itemsData = [];
        if ($settlement->items_data) {
            $itemsData = is_string($settlement->items_data)
                ? json_decode($settlement->items_data, true)
                : $settlement->items_data;
        }

        $details = [
            'settlement_code' => $settlement->settlement_code,
            'boutique_name' => $settlement->boutique_name,
            'month' => $settlement->month,
            'date_from' => $settlement->date_from ? $settlement->date_from->format('Y-m-d') : null,
            'date_to' => $settlement->date_to ? $settlement->date_to->format('Y-m-d') : null,
            'number_of_items' => $settlement->number_of_items,
            'total_sales' => (float) $settlement->total_sales,
            'total_difference' => $settlement->total_difference,
            'attachment_path' => $settlement->attachment_path,
            'attachment_name' => $settlement->attachment_name,
            'notes' => $settlement->notes,
            'items_data' => $itemsData,
            'added_by' => $settlement->added_by,
            'created_at' => $settlement->created_at ? $settlement->created_at->format('Y-m-d H:i:s') : null,
        ];

        return response()->json($details);
    }
}
