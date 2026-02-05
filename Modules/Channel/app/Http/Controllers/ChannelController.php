<?php

namespace Modules\Channel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChannelStock;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\PosOrdersDetail;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index()
    {
        return view('channel::channel');
    }

    public function getchannels()
    {
        return Channel::orderBy('id', 'DESC')->paginate(10);
    }

    public function store(Request $request)
    {
        $channel = new Channel();
        $channel->channel_name_en = $request->channel_name_en;
        $channel->channel_name_ar = $request->channel_name_ar;
        $channel->added_by = 'system';
        $channel->user_id = 1;
        $channel->status_for_pos = 1;

        $channel->save();

        return response()->json($channel);
    }

    public function update(Request $request, Channel $channel)
    {
        $channel->channel_name_en = $request->channel_name_en;
        $channel->channel_name_ar = $request->channel_name_ar;
        $channel->updated_by = 'system_update';
        $channel->save();

        return response()->json($channel);
    }

    public function show(Channel $channel)
    {
        return response()->json($channel);
    }

    public function destroy(Channel $channel)
    {
        if ($channel->id == 1) {
            return response()->json([
                'error' => true,
                'message' => trans('messages.cannot_delete_primary_channel', [], session('locale')) ?: 'Cannot delete primary channel (ID: 1)'
            ], 403);
        }

        $channel->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function updateStatus(Request $request, Channel $channel)
    {
        if ($channel->id == 1) {
            return response()->json([
                'error' => true,
                'message' => trans('messages.cannot_update_primary_channel_status', [], session('locale')) ?: 'Cannot update status for primary channel (ID: 1)'
            ], 403);
        }

        $request->validate([
            'status_for_pos' => 'required|integer|in:1,2'
        ]);

        $channel->status_for_pos = $request->status_for_pos;
        $channel->updated_by = 'system_update';
        $channel->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'channel' => $channel
        ]);
    }

    public function profile($id)
    {
        $channel = Channel::findOrFail($id);

        $channelLocation = 'channel-' . $id;
        $totalQuantitySent = TransferItem::where('to_location', $channelLocation)
            ->sum('quantity');

        $totalTransfers = Transfer::where(function ($query) use ($id, $channelLocation) {
            $query->where('channel_id', $id)
                ->orWhere('from', $channelLocation)
                ->orWhere('to', $channelLocation);
        })->count();

        $posOrders = \App\Models\PosOrders::where('channel_id', $id)->get();
        $totalSales = $posOrders->sum('total_amount');
        $totalOrders = $posOrders->count();
        $totalItemsSold = \App\Models\PosOrdersDetail::where('channel_id', $id)->sum('item_quantity');
        $totalProfit = \App\Models\PosOrdersDetail::where('channel_id', $id)->sum('item_profit');
        $availableItems = max(0, $totalQuantitySent - $totalItemsSold);

        return view('channel::channel_profile', compact('channel', 'totalQuantitySent', 'totalTransfers', 'totalSales', 'totalOrders', 'totalItemsSold', 'totalProfit', 'availableItems'));
    }

    public function getTransfers(Request $request, $id)
    {
        $channelLocation = 'channel-' . $id;
        $transfers = Transfer::where(function ($query) use ($id, $channelLocation) {
            $query->where('channel_id', $id)
                ->orWhere('from', $channelLocation)
                ->orWhere('to', $channelLocation);
        })
            ->with('items')
            ->orderBy('date', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->get();

        $formattedTransfers = $transfers->map(function ($transfer) {
            return [
                'id' => $transfer->id,
                'transfer_code' => $transfer->transfer_code,
                'transfer_type' => $transfer->transfer_type,
                'channel_type' => $transfer->channel_type,
                'date' => $transfer->date ? $transfer->date->format('Y-m-d') : null,
                'quantity' => $transfer->quantity,
                'from' => $transfer->from,
                'to' => $transfer->to,
                'notes' => $transfer->notes,
                'added_by' => $transfer->added_by,
                'created_at' => $transfer->created_at ? $transfer->created_at->format('Y-m-d H:i:s') : null,
                'items_count' => $transfer->items->count(),
            ];
        });

        return response()->json($formattedTransfers);
    }

    public function getTransferItems(Request $request, $id)
    {
        $channelLocation = 'channel-' . $id;
        $transferItems = TransferItem::whereHas('transfer', function ($query) use ($id, $channelLocation) {
            $query->where('channel_id', $id)
                ->orWhere('from', $channelLocation)
                ->orWhere('to', $channelLocation);
        })
            ->with(['transfer' => function ($query) {
                $query->select('id', 'transfer_code', 'date', 'transfer_type');
            }])
            ->orderBy('created_at', 'DESC')
            ->get();

        $formattedItems = $transferItems->map(function ($item) use ($id) {
            $soldQuantity = \App\Models\PosOrdersDetail::where('channel_id', $id)
                ->where('item_id', $item->stock_id)
                ->where(function ($query) use ($item) {
                    if ($item->color_id) {
                        $query->where('color_id', $item->color_id);
                    } else {
                        $query->whereNull('color_id');
                    }
                })
                ->where(function ($query) use ($item) {
                    if ($item->size_id) {
                        $query->where('size_id', $item->size_id);
                    } else {
                        $query->whereNull('size_id');
                    }
                })
                ->sum('item_quantity');

            $status = ($soldQuantity > 0) ? 'sold' : 'available';

            return [
                'id' => $item->id,
                'transfer_id' => $item->transfer_id,
                'transfer_code' => $item->transfer->transfer_code ?? null,
                'transfer_date' => $item->transfer->date ? $item->transfer->date->format('Y-m-d') : null,
                'stock_id' => $item->stock_id,
                'abaya_code' => $item->abaya_code,
                'item_type' => $item->item_type,
                'color_id' => $item->color_id,
                'size_id' => $item->size_id,
                'color_name' => $item->color_name,
                'size_name' => $item->size_name,
                'quantity' => $item->quantity,
                'from_location' => $item->from_location,
                'to_location' => $item->to_location,
                'status' => $status,
                'sold_quantity' => $soldQuantity,
                'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json($formattedItems);
    }

    public function getSales(Request $request, $id)
    {
        $sales = PosOrdersDetail::where('channel_id', $id)
            ->with(['order', 'stock', 'color', 'size'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $formattedSales = $sales->map(function ($sale) {
            $locale = session('locale', 'en');
            $colorName = $sale->color ? ($locale === 'ar' ? ($sale->color->color_name_ar ?? $sale->color->color_name_en) : ($sale->color->color_name_en ?? $sale->color->color_name_ar)) : '-';
            $sizeName = $sale->size ? ($locale === 'ar' ? ($sale->size->size_name_ar ?? $sale->size->size_name_en) : ($sale->size->size_name_en ?? $sale->size->size_name_ar)) : '-';

            $costPrice = $sale->stock ? (float)($sale->stock->cost_price ?? 0) : 0;
            $salesPrice = $sale->stock ? (float)($sale->stock->sales_price ?? 0) : 0;
            $soldPrice = (float)($sale->item_price ?? 0);
            $profit = (float)($sale->item_profit ?? 0);

            return [
                'id' => $sale->id,
                'order_id' => $sale->order_id,
                'order_no' => $sale->order_no,
                'order_date' => $sale->order && $sale->order->created_at ? $sale->order->created_at->format('Y-m-d') : null,
                'order_datetime' => $sale->order && $sale->order->created_at ? $sale->order->created_at->format('Y-m-d H:i:s') : null,
                'stock_id' => $sale->item_id,
                'abaya_code' => $sale->stock ? $sale->stock->abaya_code : '-',
                'design_name' => $sale->stock ? $sale->stock->design_name : '-',
                'barcode' => $sale->stock ? $sale->stock->barcode : '-',
                'color_id' => $sale->color_id,
                'size_id' => $sale->size_id,
                'color_name' => $colorName,
                'size_name' => $sizeName,
                'quantity' => $sale->item_quantity,
                'cost_price' => $costPrice,
                'sales_price' => $salesPrice,
                'sold_price' => $soldPrice,
                'total' => $sale->item_total,
                'profit' => $profit,
                'customer_name' => $sale->order && $sale->order->customer ? $sale->order->customer->name : '-',
                'customer_phone' => $sale->order && $sale->order->customer ? $sale->order->customer->phone : '-',
                'created_at' => $sale->created_at ? $sale->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json($formattedSales);
    }

    public function getItemStatus(Request $request, $id)
    {
        $channelLocation = 'channel-' . $id;

        $transferItems = TransferItem::where('to_location', $channelLocation)
            ->with('stock')
            ->get();

        $groupedItems = $transferItems->groupBy(function ($item) {
            return $item->stock_id . '_' . ($item->color_id ?? 'null') . '_' . ($item->size_id ?? 'null');
        });

        $formattedItems = [];

        foreach ($groupedItems as $key => $items) {
            $firstItem = $items->first();
            $totalQuantity = $items->sum('quantity');
            $stock = $firstItem->stock;
            $designName = $stock ? $stock->design_name : '-';
            $abayaCode = $firstItem->abaya_code;
            $colorName = $firstItem->color_name ?? '-';
            $sizeName = $firstItem->size_name ?? '-';

            $soldQuantity = PosOrdersDetail::where('channel_id', $id)
                ->where('item_id', $firstItem->stock_id)
                ->where(function ($query) use ($firstItem) {
                    if ($firstItem->color_id) {
                        $query->where('color_id', $firstItem->color_id);
                    } else {
                        $query->whereNull('color_id');
                    }
                })
                ->where(function ($query) use ($firstItem) {
                    if ($firstItem->size_id) {
                        $query->where('size_id', $firstItem->size_id);
                    } else {
                        $query->whereNull('size_id');
                    }
                })
                ->sum('item_quantity');

            $status = ($soldQuantity > 0) ? 'sold' : 'available';

            $formattedItems[] = [
                'id' => $firstItem->id,
                'stock_id' => $firstItem->stock_id,
                'abaya_code' => $abayaCode,
                'design_name' => $designName,
                'color_id' => $firstItem->color_id,
                'size_id' => $firstItem->size_id,
                'color_name' => $colorName,
                'size_name' => $sizeName,
                'quantity' => $totalQuantity,
                'sold_quantity' => $soldQuantity,
                'status' => $status,
            ];
        }

        usort($formattedItems, function ($a, $b) {
            if ($a['abaya_code'] != $b['abaya_code']) {
                return strcmp($a['abaya_code'], $b['abaya_code']);
            }
            if ($a['color_name'] != $b['color_name']) {
                return strcmp($a['color_name'], $b['color_name']);
            }
            return strcmp($a['size_name'], $b['size_name']);
        });

        return response()->json($formattedItems);
    }
}
