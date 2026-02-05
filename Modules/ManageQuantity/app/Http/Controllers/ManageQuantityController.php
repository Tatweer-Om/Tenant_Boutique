<?php

namespace Modules\ManageQuantity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Channel;
use App\Models\Boutique;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\TransferItemHistory;
use App\Models\ChannelStock;
use App\Models\ColorSize;
use App\Models\StockSize;
use App\Models\StockColor;
use App\Models\StockAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManageQuantityController extends Controller
{
    public function manage_quantity()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(6, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        $locale = session('locale');
        $total_stock = Stock::sum('quantity');

        $boutiques = Boutique::select('id', 'boutique_name')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => (int)$item->id,
                    'type' => 'boutique',
                    'display_name' => $item->boutique_name
                ];
            })
            ->toArray();

        $channels = Channel::select('id', 'channel_name_en', 'channel_name_ar')
            ->get()
            ->map(function ($item) use ($locale) {
                return [
                    'id' => (int)$item->id,
                    'type' => 'channel',
                    'display_name' => $locale == 'ar'
                        ? $item->channel_name_ar
                        : $item->channel_name_en
                ];
            })
            ->toArray();

        $items = array_merge($boutiques, $channels);

        return view('manage_quantity::manage_quantity', compact('total_stock', 'items'));
    }

    public function get_inventory(Request $request)
    {
        $locale = session('locale');
        $full = $request->boolean('full');
        $perPage = (int) $request->input('per_page', 70);
        $perPage = min(100, max(1, $perPage));
        $page = max(1, (int) $request->input('page', 1));

        if ($full) {
            $inventory = $this->buildFullInventory($locale);
            return response()->json($inventory);
        }

        $unionSql = "
            ( SELECT s.id as stock_id, s.abaya_code, s.design_name, s.barcode, 'size' as mode,
              sz.size_name_ar, sz.size_name_en, NULL as color_name_ar, NULL as color_name_en, NULL as color_code,
              ss.qty, NULL as color_id, ss.size_id
              FROM stocks s
              INNER JOIN stock_sizes ss ON ss.stock_id = s.id AND ss.qty > 0
              INNER JOIN sizes sz ON sz.id = ss.size_id
              WHERE s.mode = 'size' )
            UNION ALL
            ( SELECT s.id, s.abaya_code, s.design_name, s.barcode, 'color',
              NULL, NULL, c.color_name_ar, c.color_name_en, COALESCE(c.color_code,'#000000'),
              sc.qty, sc.color_id, NULL
              FROM stocks s
              INNER JOIN stock_colors sc ON sc.stock_id = s.id AND sc.qty > 0
              INNER JOIN colors c ON c.id = sc.color_id
              WHERE s.mode = 'color' )
            UNION ALL
            ( SELECT s.id, s.abaya_code, s.design_name, s.barcode, 'color_size',
              sz.size_name_ar, sz.size_name_en, c.color_name_ar, c.color_name_en, COALESCE(c.color_code,'#000000'),
              cs.qty, cs.color_id, cs.size_id
              FROM stocks s
              INNER JOIN color_sizes cs ON cs.stock_id = s.id AND cs.qty > 0
              INNER JOIN sizes sz ON sz.id = cs.size_id
              INNER JOIN colors c ON c.id = cs.color_id
              WHERE s.mode = 'color_size' )
        ";
        $countResult = DB::selectOne("SELECT COUNT(*) as total FROM ({$unionSql}) as u");
        $total = (int) ($countResult->total ?? 0);
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $rows = DB::select(
            "SELECT * FROM ({$unionSql}) as u ORDER BY abaya_code, mode, size_name_en, color_name_en LIMIT " . (int) $perPage . " OFFSET " . (int) $offset
        );

        $inventory = [];
        foreach ($rows as $row) {
            $code = $row->abaya_code;
            $name = $row->design_name;
            $mode = $row->mode;
            $sizeName = $locale == 'ar' ? $row->size_name_ar : $row->size_name_en;
            $colorName = $locale == 'ar' ? $row->color_name_ar : $row->color_name_en;
            $colorCode = $row->color_code ?? '#000000';
            if ($mode === 'size') {
                $uid = $code . '|' . ($sizeName ?: '') . '|';
                $inventory[] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'size',
                    'size' => $sizeName,
                    'color' => null,
                    'color_code' => '#000000',
                    'available' => (int) $row->qty,
                    'webiste_available' => 0
                ];
            } elseif ($mode === 'color') {
                $uid = $code . '||' . ($colorName ?: '');
                $inventory[] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'color',
                    'size' => null,
                    'color' => $colorName,
                    'color_code' => $colorCode,
                    'available' => (int) $row->qty,
                    'webiste_available' => 0
                ];
            } else {
                $uid = $code . '|' . ($sizeName ?: '') . '|' . ($colorName ?: '');
                $webisteQty = 0;
                if ($row->stock_id && $row->color_id && $row->size_id && function_exists('fetchWebsiteCurrentQty')) {
                    $barcode = DB::table('stocks')->where('id', $row->stock_id)->value('barcode');
                    $webisteQty = (int) fetchWebsiteCurrentQty($row->stock_id, $barcode, $row->color_id, $row->size_id);
                }
                $inventory[] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => 'color_size',
                    'size' => $sizeName,
                    'color' => $colorName,
                    'color_code' => $colorCode,
                    'available' => (int) $row->qty,
                    'webiste_available' => $webisteQty
                ];
            }
        }

        return response()->json([
            'data' => $inventory,
            'total' => $total,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage
        ]);
    }

    private function buildFullInventory($locale)
    {
        $inventory = [];
        $stocks = Stock::with(['sizes.size', 'colors.color', 'colorSizes.color', 'colorSizes.size'])
            ->get();

        foreach ($stocks as $stock) {
            $code = $stock->abaya_code;
            $name = $stock->design_name;
            $mode = $stock->mode;
            $barcode = $stock->barcode ?? '';

            if ($mode === 'size') {
                foreach ($stock->sizes ?? [] as $stockSize) {
                    if ((int) $stockSize->qty <= 0) continue;
                    $size = $stockSize->size;
                    $sizeName = $size ? ($locale == 'ar' ? $size->size_name_ar : $size->size_name_en) : null;
                    $uid = $code . '|' . ($sizeName ?: '') . '|';
                    $inventory[] = [
                        'uid' => $uid,
                        'code' => $code,
                        'name' => $name,
                        'type' => 'size',
                        'size' => $sizeName,
                        'color' => null,
                        'color_code' => '#000000',
                        'available' => (int) $stockSize->qty,
                        'webiste_available' => 0
                    ];
                }
            } elseif ($mode === 'color') {
                foreach ($stock->colors ?? [] as $stockColor) {
                    if ((int) $stockColor->qty <= 0) continue;
                    $color = $stockColor->color;
                    $colorName = $color ? ($locale == 'ar' ? $color->color_name_ar : $color->color_name_en) : null;
                    $colorCode = $color ? ($color->color_code ?? '#000000') : '#000000';
                    $uid = $code . '||' . ($colorName ?: '');
                    $inventory[] = [
                        'uid' => $uid,
                        'code' => $code,
                        'name' => $name,
                        'type' => 'color',
                        'size' => null,
                        'color' => $colorName,
                        'color_code' => $colorCode,
                        'available' => (int) $stockColor->qty,
                        'webiste_available' => 0
                    ];
                }
            } elseif ($mode === 'color_size') {
                foreach ($stock->colorSizes ?? [] as $colorSize) {
                    if ((int) $colorSize->qty <= 0) continue;
                    $result = function_exists('fetchWebsiteCurrentQty') ? fetchWebsiteCurrentQty($colorSize->stock_id, $barcode, $colorSize->color_id, $colorSize->size_id) : 0;
                    $color = $colorSize->color;
                    $size = $colorSize->size;
                    $colorName = $color ? ($locale == 'ar' ? $color->color_name_ar : $color->color_name_en) : null;
                    $colorCode = $color ? ($color->color_code ?? '#000000') : '#000000';
                    $sizeName = $size ? ($locale == 'ar' ? $size->size_name_ar : $size->size_name_en) : null;
                    $uid = $code . '|' . ($sizeName ?: '') . '|' . ($colorName ?: '');
                    $inventory[] = [
                        'uid' => $uid,
                        'code' => $code,
                        'name' => $name,
                        'type' => 'color_size',
                        'size' => $sizeName,
                        'color' => $colorName,
                        'color_code' => $colorCode,
                        'available' => (int) $colorSize->qty,
                        'webiste_available' => (int) $result
                    ];
                }
            }
        }
        return $inventory;
    }

    public function get_channel_inventory(Request $request)
    {
        $channelId = $request->input('channel_id');
        if (!$channelId) {
            return response()->json([]);
        }

        $parts = explode('-', $channelId);
        if (count($parts) < 2) {
            return response()->json([]);
        }

        $channelType = strpos($channelId, 'boutique-') === 0 ? 'boutique' : 'channel';
        $locationId = (int)$parts[1];
        if ($locationId <= 0) {
            return response()->json([]);
        }

        $channelStocks = ChannelStock::where('location_type', $channelType)
            ->where('location_id', $locationId)
            ->where('quantity', '>', 0)
            ->with(['stock'])
            ->get();

        $groupedInventory = [];
        foreach ($channelStocks as $channelStock) {
            $stock = $channelStock->stock;
            if (!$stock) continue;

            $code = $channelStock->abaya_code;
            if (!$code) continue;

            $name = $stock->design_name ?? '';
            $itemType = $channelStock->item_type ?? 'color_size';
            $sizeName = $channelStock->size_name;
            $colorName = $channelStock->color_name;

            if ($itemType === 'color_size') {
                $uid = $code . '|' . ($sizeName ?: '') . '|' . ($colorName ?: '');
            } elseif ($itemType === 'color') {
                $uid = $code . '||' . ($colorName ?: '');
            } else {
                $uid = $code . '|' . ($sizeName ?: '') . '|';
            }

            $colorCode = '#000000';
            if ($channelStock->color_id) {
                $color = \App\Models\Color::find($channelStock->color_id);
                $colorCode = $color ? ($color->color_code ?? '#000000') : '#000000';
            }

            if (!isset($groupedInventory[$uid])) {
                $groupedInventory[$uid] = [
                    'uid' => $uid,
                    'code' => $code,
                    'name' => $name,
                    'type' => $itemType,
                    'size' => $sizeName,
                    'color' => $colorName,
                    'color_code' => $colorCode,
                    'available' => 0
                ];
            }
            $groupedInventory[$uid]['available'] += (int)$channelStock->quantity;
        }

        return response()->json(array_values($groupedInventory));
    }

    public function execute_transfer(Request $request)
    {
        try {
            DB::beginTransaction();

            $user_id = Auth::guard('tenant')->id();
            $user_name = Auth::guard('tenant')->user()->user_name ?? 'System';

            $mode = $request->input('mode');
            $fromChannel = $request->input('from');
            $toChannel = $request->input('to');
            $transferDate = $request->input('transfer_date') ?: date('Y-m-d');
            $transferNote = $request->input('note', '');
            $basket = $request->input('basket', []);

            if (empty($basket)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Basket is empty'
                ], 400);
            }

            $transferCode = 'TR-' . date('Ymd') . '-' . str_pad(Transfer::count() + 1, 3, '0', STR_PAD_LEFT);
            $transferType = 'transfer';
            $channelType = 'channel';
            if (strpos($toChannel, 'boutique-') === 0) {
                $channelType = 'boutique';
            }

            $fromType = $fromChannel === 'main' ? 'main' : (strpos($fromChannel, 'boutique-') === 0 ? 'boutique' : 'channel');
            $toType = $toChannel === 'main' ? 'main' : (strpos($toChannel, 'boutique-') === 0 ? 'boutique' : 'channel');
            $fromId = $fromChannel === 'main' ? null : (int)explode('-', $fromChannel)[1];
            $toId = $toChannel === 'main' ? null : (int)explode('-', $toChannel)[1];
            $totalQuantity = array_sum(array_column($basket, 'qty'));

            $transfer = new Transfer();
            $transfer->transfer_code = $transferCode;
            $transfer->transfer_type = $transferType;
            $transfer->channel_type = $channelType;
            $transfer->date = $transferDate;
            $transfer->quantity = $totalQuantity;
            $transfer->sellable = (strpos($toChannel, 'boutique-') === 0) ? $totalQuantity : 0;
            $transfer->from = $fromChannel;
            $transfer->to = $toChannel;
            $transfer->boutique_id = $toType === 'boutique' ? $toId : null;
            $transfer->channel_id = $toType === 'channel' ? $toId : null;
            $transfer->notes = $transferNote;
            $transfer->added_by = $user_name;
            $transfer->user_id = $user_id;
            $transfer->save();

            foreach ($basket as $item) {
                $stock = Stock::where('abaya_code', $item['code'])->first();
                if (!$stock) continue;

                $itemType = $item['type'] ?? 'color_size';
                $colorId = null;
                $sizeId = null;
                $colorName = $item['color'] ?? null;
                $sizeName = $item['size'] ?? null;

                if ($colorName) {
                    $color = \App\Models\Color::where(function($q) use ($colorName) {
                        $q->where('color_name_ar', $colorName)->orWhere('color_name_en', $colorName);
                    })->first();
                    $colorId = $color ? $color->id : null;
                }
                if ($sizeName) {
                    $size = \App\Models\Size::where(function($q) use ($sizeName) {
                        $q->where('size_name_ar', $sizeName)->orWhere('size_name_en', $sizeName);
                    })->first();
                    $sizeId = $size ? $size->id : null;
                }

                $transferItem = TransferItem::where('transfer_id', $transfer->id)
                    ->where('abaya_code', $item['code'])
                    ->where('color_id', $colorId)
                    ->where('size_id', $sizeId)
                    ->first();

                if ($transferItem) {
                    $transferItem->quantity += (int)$item['qty'];
                    if (strpos($toChannel, 'boutique-') === 0) {
                        $transferItem->sellable += (int)$item['qty'];
                    }
                    $transferItem->save();
                } else {
                    $transferItem = new TransferItem();
                    $transferItem->transfer_id = $transfer->id;
                    $transferItem->stock_id = $stock->id;
                    $transferItem->abaya_code = $item['code'];
                    $transferItem->item_type = $itemType;
                    $transferItem->color_id = $colorId;
                    $transferItem->size_id = $sizeId;
                    $transferItem->color_name = $colorName;
                    $transferItem->size_name = $sizeName;
                    $transferItem->sellable = (strpos($toChannel, 'boutique-') === 0) ? (int)$item['qty'] : 0;
                    $transferItem->quantity = (int)$item['qty'];
                    $transferItem->from_location = $fromChannel;
                    $transferItem->to_location = $toChannel;
                    $transferItem->added_by = $user_name;
                    $transferItem->user_id = $user_id;
                    $transferItem->save();
                }

                $this->updateChannelStock($stock->id, $item['code'], $itemType, $colorId, $sizeId, $colorName, $sizeName, $fromChannel, $toChannel, (int)$item['qty']);

                if ($fromChannel === 'main') {
                    $currentQty = 0;
                    if ($itemType === 'color_size' && $colorId && $sizeId) {
                        $colorSize = ColorSize::where('stock_id', $stock->id)->where('color_id', $colorId)->where('size_id', $sizeId)->first();
                        $currentQty = $colorSize ? (int)$colorSize->qty : 0;
                    }
                    $this->decreaseMainWarehouseStock($stock->id, $itemType, $colorId, $sizeId, (int)$item['qty']);
                    $newQty = max(0, $currentQty - (int)$item['qty']);
                    $toWhom = $toType === 'boutique' ? (Boutique::find($toId)->boutique_name ?? $toChannel) : ($toType === 'channel' ? (Channel::find($toId)->channel_name_en ?? $toChannel) : $toChannel);
                    StockAuditLog::create([
                        'stock_id' => $stock->id,
                        'abaya_code' => $stock->abaya_code,
                        'barcode' => $stock->barcode,
                        'design_name' => $stock->design_name,
                        'operation_type' => 'transferred',
                        'previous_quantity' => $currentQty,
                        'new_quantity' => $newQty,
                        'quantity_change' => -(int)$item['qty'],
                        'related_id' => $transferCode,
                        'related_type' => 'transfer',
                        'related_info' => ['to' => $toWhom, 'from' => 'Main Warehouse'],
                        'color_id' => $colorId,
                        'size_id' => $sizeId,
                        'user_id' => $user_id,
                        'added_by' => $user_name,
                        'notes' => 'Transferred out',
                    ]);
                }
                if ($toChannel === 'main') {
                    $currentQty = 0;
                    if ($itemType === 'color_size' && $colorId && $sizeId) {
                        $colorSize = ColorSize::where('stock_id', $stock->id)->where('color_id', $colorId)->where('size_id', $sizeId)->first();
                        $currentQty = $colorSize ? (int)$colorSize->qty : 0;
                    }
                    $this->increaseMainWarehouseStock($stock->id, $itemType, $colorId, $sizeId, (int)$item['qty']);
                    $newQty = $currentQty + (int)$item['qty'];
                    $fromWhom = $fromType === 'boutique' ? (Boutique::find($fromId)->boutique_name ?? $fromChannel) : ($fromType === 'channel' ? (Channel::find($fromId)->channel_name_en ?? $fromChannel) : $fromChannel);
                    StockAuditLog::create([
                        'stock_id' => $stock->id,
                        'abaya_code' => $stock->abaya_code,
                        'barcode' => $stock->barcode,
                        'design_name' => $stock->design_name,
                        'operation_type' => 'transferred',
                        'previous_quantity' => $currentQty,
                        'new_quantity' => $newQty,
                        'quantity_change' => (int)$item['qty'],
                        'related_id' => $transferCode,
                        'related_type' => 'transfer',
                        'related_info' => ['from' => $fromWhom, 'to' => 'Main Warehouse'],
                        'color_id' => $colorId,
                        'size_id' => $sizeId,
                        'user_id' => $user_id,
                        'added_by' => $user_name,
                        'notes' => 'Transferred in',
                    ]);
                }

                $history = new TransferItemHistory();
                $history->transfer_id = $transfer->id;
                $history->item_code = $item['code'];
                $history->item_size = $sizeName;
                $history->item_color = $colorName;
                $history->item_previous_quantity = $item['available'] ?? 0;
                $history->quantity_action = $fromChannel === 'main' ? 'pulled' : 'transferred';
                $history->item_new_quantity = ($item['available'] ?? 0) - (int)$item['qty'];
                $history->quantity_pulled = $fromChannel === 'main' ? (int)$item['qty'] : 0;
                $history->quantity_pushed = $toChannel === 'main' ? (int)$item['qty'] : 0;
                $history->added_by = $user_name;
                $history->user_id = $user_id;
                $history->save();

                if (strpos($fromChannel, 'boutique-') === 0) {
                    $this->updateSellableOnPull($fromChannel, $item['code'], $colorName, $sizeName, (int)$item['qty'], $colorId, $sizeId);
                }
            }

            DB::commit();

            try {
                if (function_exists('syncTransferItemsToWebsiteReceiveQty')) {
                    syncTransferItemsToWebsiteReceiveQty($transfer->id, 'channel-1');
                }
            } catch (\Throwable $e) {
                \Log::error('Transfer items website sync failed', ['transfer_id' => $transfer->id ?? null, 'error' => $e->getMessage()]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Transfer executed successfully',
                'transfer_code' => $transferCode
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Error executing transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function get_transfer_history(Request $request)
    {
        $search = $request->input('search', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Transfer::with('items')->orderBy('date', 'desc')->orderBy('id', 'desc');
        if ($search) $query->where('transfer_code', 'like', '%' . $search . '%');
        if ($dateFrom) $query->where('date', '>=', $dateFrom);
        if ($dateTo) $query->where('date', '<=', $dateTo);

        $transfers = $query->get();
        $history = [];
        foreach ($transfers as $transfer) {
            $items = [];
            foreach ($transfer->items as $item) {
                $items[] = [
                    'code' => $item->abaya_code,
                    'color' => $item->color_name,
                    'size' => $item->size_name,
                    'qty' => $item->quantity,
                ];
            }
            $history[] = [
                'no' => $transfer->transfer_code,
                'date' => $transfer->date->format('Y-m-d'),
                'from' => $transfer->from,
                'to' => $transfer->to,
                'total' => $transfer->quantity,
                'items' => $items,
                'note' => $transfer->notes ?? '',
            ];
        }
        return response()->json($history);
    }

    public function export_transfers_excel(Request $request)
    {
        $locale = session('locale');
        $search = $request->input('search', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Transfer::with('items')->orderBy('date', 'desc')->orderBy('id', 'desc');
        if ($search) $query->where('transfer_code', 'like', '%' . $search . '%');
        if ($dateFrom) $query->where('date', '>=', $dateFrom);
        if ($dateTo) $query->where('date', '<=', $dateTo);

        $transfers = $query->get();
        $getLocationName = function($locationId) use ($locale) {
            if ($locationId === 'main') return trans('messages.main_warehouse', [], $locale);
            if (strpos($locationId, 'boutique-') === 0) {
                $id = (int)explode('-', $locationId)[1];
                $boutique = Boutique::find($id);
                return $boutique ? $boutique->boutique_name : $locationId;
            }
            if (strpos($locationId, 'channel-') === 0) {
                $id = (int)explode('-', $locationId)[1];
                $channel = Channel::find($id);
                return $channel ? ($locale == 'ar' ? $channel->channel_name_ar : $channel->channel_name_en) : $locationId;
            }
            return $locationId;
        };

        $filename = 'transfers_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $output = "\xEF\xBB\xBF";
        $output .= trans('messages.operation_number', [], $locale) . ',';
        $output .= trans('messages.date', [], $locale) . ',';
        $output .= trans('messages.from', [], $locale) . ',';
        $output .= trans('messages.to', [], $locale) . ',';
        $output .= trans('messages.number_of_items', [], $locale) . ',';
        $output .= trans('messages.total_quantity', [], $locale) . ',';
        $output .= trans('messages.items_details', [], $locale) . ',';
        $output .= trans('messages.operation_notes', [], $locale) . "\n";

        foreach ($transfers as $transfer) {
            $itemsDetails = [];
            foreach ($transfer->items as $item) {
                $itemStr = $item->abaya_code;
                if ($item->size_name) $itemStr .= ' - ' . trans('messages.size', [], $locale) . ': ' . $item->size_name;
                if ($item->color_name) $itemStr .= ' - ' . trans('messages.color', [], $locale) . ': ' . $item->color_name;
                $itemStr .= ' (' . $item->quantity . ' ' . trans('messages.pieces', [], $locale) . ')';
                $itemsDetails[] = $itemStr;
            }
            $fromName = $getLocationName($transfer->from);
            $toName = $getLocationName($transfer->to);
            $itemsText = implode('; ', $itemsDetails);
            $notes = str_replace(["\r\n", "\n", "\r"], ' ', $transfer->notes ?? '');
            $output .= '"' . str_replace('"', '""', $transfer->transfer_code) . '",';
            $output .= '"' . str_replace('"', '""', $transfer->date->format('Y-m-d')) . '",';
            $output .= '"' . str_replace('"', '""', $fromName) . '",';
            $output .= '"' . str_replace('"', '""', $toName) . '",';
            $output .= '"' . str_replace('"', '""', count($transfer->items)) . '",';
            $output .= '"' . str_replace('"', '""', $transfer->quantity) . '",';
            $output .= '"' . str_replace('"', '""', $itemsText) . '",';
            $output .= '"' . str_replace('"', '""', $notes) . '"' . "\n";
        }
        return response($output, 200, $headers);
    }

    public function get_channel_stocks(Request $request)
    {
        $channelId = $request->input('channel_id');
        if (!$channelId) return response()->json([]);

        $channelType = strpos($channelId, 'boutique-') === 0 ? 'boutique' : 'channel';
        $locationId = (int)explode('-', $channelId)[1];

        $stocks = ChannelStock::where('location_type', $channelType)
            ->where('location_id', $locationId)
            ->with('stock')
            ->get();

        $unsettledMap = [];
        if ($channelType === 'boutique') {
            $boutiqueLocation = 'boutique-' . $locationId;
            $sent = TransferItem::select('abaya_code', 'color_id', 'size_id', 'color_name', 'size_name', DB::raw('SUM(sellable) as sellable_sum'))
                ->where('to_location', $boutiqueLocation)
                ->groupBy('abaya_code', 'color_id', 'size_id', 'color_name', 'size_name')
                ->get();
            $pulled = TransferItem::select('abaya_code', 'color_id', 'size_id', 'color_name', 'size_name', DB::raw('SUM(quantity) as pulled_sum'))
                ->where('from_location', $boutiqueLocation)
                ->groupBy('abaya_code', 'color_id', 'size_id', 'color_name', 'size_name')
                ->get();
            $pulledMap = [];
            foreach ($pulled as $p) {
                $k = ($p->abaya_code ?? '') . '|' . ($p->color_id ?? 'null') . '|' . ($p->size_id ?? 'null') . '|' . ($p->color_name ?? '') . '|' . ($p->size_name ?? '');
                $pulledMap[$k] = (int)($p->pulled_sum ?? 0);
            }
            foreach ($sent as $s) {
                $k = ($s->abaya_code ?? '') . '|' . ($s->color_id ?? 'null') . '|' . ($s->size_id ?? 'null') . '|' . ($s->color_name ?? '') . '|' . ($s->size_name ?? '');
                $unsettledMap[$k] = max(0, (int)($s->sellable_sum ?? 0) - (int)($pulledMap[$k] ?? 0));
            }
        }

        $result = [];
        foreach ($stocks as $stock) {
            $key = ($stock->abaya_code ?? '') . '|' . ($stock->color_id ?? 'null') . '|' . ($stock->size_id ?? 'null') . '|' . ($stock->color_name ?? '') . '|' . ($stock->size_name ?? '');
            $unsettledQty = $channelType === 'boutique' ? (int)($unsettledMap[$key] ?? 0) : (int)($stock->quantity ?? 0);
            $result[] = [
                'code' => $stock->abaya_code,
                'color' => $stock->color_name,
                'size' => $stock->size_name,
                'qty' => $stock->quantity,
                'unsettled_qty' => $unsettledQty,
            ];
        }
        return response()->json($result);
    }

    public function get_stats()
    {
        $mainTotal = ColorSize::sum('qty') + StockColor::sum('qty') + StockSize::sum('qty');
        $websiteTotal = ChannelStock::where('location_type', 'channel')->sum('quantity');
        $posTotal = 0;
        $boutiquesTotal = ChannelStock::where('location_type', 'boutique')->sum('quantity');
        return response()->json([
            'main' => (int)$mainTotal,
            'website' => (int)$websiteTotal,
            'pos' => (int)$posTotal,
            'boutiques' => (int)$boutiquesTotal,
        ]);
    }

    public function movements_log(Request $request)
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];
        if (!in_array(6, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        $locale = session('locale');
        $search = $request->input('search', '');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = 10;

        $query = Transfer::with('items')->orderBy('date', 'desc')->orderBy('id', 'desc');
        if ($search) $query->where('transfer_code', 'like', '%' . $search . '%');
        if ($dateFrom) $query->where('date', '>=', $dateFrom);
        if ($dateTo) $query->where('date', '<=', $dateTo);

        $transfers = $query->paginate($perPage)->appends($request->query());
        $getLocationName = function($locationId) use ($locale) {
            if ($locationId === 'main') return trans('messages.main_warehouse', [], $locale);
            if (strpos($locationId, 'boutique-') === 0) {
                $id = (int)explode('-', $locationId)[1];
                $boutique = Boutique::find($id);
                return $boutique ? $boutique->boutique_name : $locationId;
            }
            if (strpos($locationId, 'channel-') === 0) {
                $id = (int)explode('-', $locationId)[1];
                $channel = Channel::find($id);
                return $channel ? ($locale == 'ar' ? $channel->channel_name_ar : $channel->channel_name_en) : $locationId;
            }
            return $locationId;
        };

        $transfers->getCollection()->transform(function ($transfer) use ($getLocationName) {
            return [
                'id' => $transfer->id,
                'no' => $transfer->transfer_code,
                'date' => $transfer->date->format('Y-m-d'),
                'from' => $getLocationName($transfer->from),
                'to' => $getLocationName($transfer->to),
                'total' => $transfer->quantity,
                'items' => $transfer->items->map(fn($item) => [
                    'code' => $item->abaya_code,
                    'color' => $item->color_name,
                    'size' => $item->size_name,
                    'qty' => $item->quantity,
                ])->toArray(),
                'note' => $transfer->notes ?? '',
            ];
        });

        return view('manage_quantity::movements_log', compact('transfers', 'search', 'dateFrom', 'dateTo'));
    }

    private function updateChannelStock($stockId, $abayaCode, $itemType, $colorId, $sizeId, $colorName, $sizeName, $fromLocation, $toLocation, $quantity)
    {
        if ($fromLocation !== 'main') {
            $fromType = strpos($fromLocation, 'boutique-') === 0 ? 'boutique' : 'channel';
            $fromId = (int)explode('-', $fromLocation)[1];
            $channelStock = ChannelStock::where('stock_id', $stockId)->where('location_type', $fromType)->where('location_id', $fromId)->where('color_id', $colorId)->where('size_id', $sizeId)->first();
            if ($channelStock) {
                $channelStock->quantity = max(0, $channelStock->quantity - $quantity);
                $channelStock->save();
            }
        }
        if ($toLocation !== 'main') {
            $toType = strpos($toLocation, 'boutique-') === 0 ? 'boutique' : 'channel';
            $toId = (int)explode('-', $toLocation)[1];
            $channelStock = ChannelStock::firstOrNew([
                'stock_id' => $stockId,
                'location_type' => $toType,
                'location_id' => $toId,
                'color_id' => $colorId,
                'size_id' => $sizeId,
            ]);
            $channelStock->abaya_code = $abayaCode;
            $channelStock->item_type = $itemType;
            $channelStock->color_name = $colorName;
            $channelStock->size_name = $sizeName;
            $channelStock->quantity = ($channelStock->quantity ?? 0) + $quantity;
            $channelStock->save();
        }
    }

    private function decreaseMainWarehouseStock($stockId, $itemType, $colorId, $sizeId, $quantity)
    {
        if ($itemType === 'color_size' && $colorId && $sizeId) {
            $colorSize = ColorSize::where('stock_id', $stockId)->where('color_id', $colorId)->where('size_id', $sizeId)->first();
            if ($colorSize) {
                $colorSize->qty = max(0, $colorSize->qty - $quantity);
                $colorSize->save();
            }
        } elseif ($itemType === 'color' && $colorId) {
            $stockColor = StockColor::where('stock_id', $stockId)->where('color_id', $colorId)->first();
            if ($stockColor) {
                $stockColor->qty = max(0, $stockColor->qty - $quantity);
                $stockColor->save();
            }
        } elseif ($itemType === 'size' && $sizeId) {
            $stockSize = StockSize::where('stock_id', $stockId)->where('size_id', $sizeId)->first();
            if ($stockSize) {
                $stockSize->qty = max(0, $stockSize->qty - $quantity);
                $stockSize->save();
            }
        }
    }

    private function increaseMainWarehouseStock($stockId, $itemType, $colorId, $sizeId, $quantity)
    {
        if ($itemType === 'color_size' && $colorId && $sizeId) {
            $colorSize = ColorSize::firstOrNew(['stock_id' => $stockId, 'color_id' => $colorId, 'size_id' => $sizeId]);
            $colorSize->qty = ($colorSize->qty ?? 0) + $quantity;
            $colorSize->save();
        } elseif ($itemType === 'color' && $colorId) {
            $stockColor = StockColor::firstOrNew(['stock_id' => $stockId, 'color_id' => $colorId]);
            $stockColor->qty = ($stockColor->qty ?? 0) + $quantity;
            $stockColor->save();
        } elseif ($itemType === 'size' && $sizeId) {
            $stockSize = StockSize::firstOrNew(['stock_id' => $stockId, 'size_id' => $sizeId]);
            $stockSize->qty = ($stockSize->qty ?? 0) + $quantity;
            $stockSize->save();
        }
    }

    private function updateSellableOnPull($boutiqueLocation, $code, $color, $size, $pulledQty, $colorId = null, $sizeId = null)
    {
        $sentTransfers = Transfer::with('items')
            ->where('to', $boutiqueLocation)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        $remainingPulledQty = $pulledQty;
        foreach ($sentTransfers as $transfer) {
            if ($remainingPulledQty <= 0) break;
            $totalTransferQty = 0;
            $matchingItemQty = 0;
            foreach ($transfer->items as $item) {
                $codeMatch = $item->abaya_code === $code;
                $sizeMatch = ($item->size_name ?? '') === ($size ?? '');
                $colorMatch = ($item->color_name ?? '') === ($color ?? '');
                if ($colorId !== null) $colorMatch = $colorMatch && ($item->color_id == $colorId);
                if ($sizeId !== null) $sizeMatch = $sizeMatch && ($item->size_id == $sizeId);
                if ($codeMatch && $sizeMatch && $colorMatch) $matchingItemQty += (int)$item->quantity;
                $totalTransferQty += (int)$item->quantity;
            }
            if ($matchingItemQty > 0) {
                $currentSellable = (int)($transfer->sellable ?? $transfer->quantity);
                $decreaseAmount = $totalTransferQty > 0 ? min($remainingPulledQty, (int)round(($currentSellable * $matchingItemQty) / $totalTransferQty)) : min($remainingPulledQty, $currentSellable);
                $transfer->sellable = max(0, $currentSellable - $decreaseAmount);
                $transfer->save();
                $remainingPulledQty -= $decreaseAmount;
            }
        }
    }
}
