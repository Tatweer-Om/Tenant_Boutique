<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelStock;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    public function index() {
        return view('modules.channel');
    }

public function getchannels() {
    return Channel::orderBy('id', 'DESC')->paginate(10);
}


 public function store(Request $request)
{
        $channel = new Channel();
        $channel->channel_name_en = $request->channel_name_en;
        $channel->channel_name_ar = $request->channel_name_ar;
        $channel->added_by = 'system';          
        $channel->user_id = 1;
        $channel->status_for_pos = 1; // Default to active

    $channel->save();

    return response()->json($channel);
}

    public function update(Request $request, channel $channel) {

    $channel->channel_name_en = $request->channel_name_en;
    $channel->channel_name_ar = $request->channel_name_ar;
    $channel->updated_by = 'system_update';
    $channel->save();

    return response()->json($channel);
    }
    public function show(channel $channel) {
    return response()->json($channel);
}

    public function destroy(channel $channel) {
        $channel->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function updateStatus(Request $request, channel $channel) {
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

    public function profile($id) {
        $channel = Channel::findOrFail($id);
        
        // Get total items count from channel_stocks
        $totalItems = ChannelStock::where('location_type', 'channel')
            ->where('location_id', $id)
            ->sum('quantity');
        
        // Get total transfers count (where channel_id matches OR channel appears in from/to)
        $channelLocation = 'channel-' . $id;
        $totalTransfers = Transfer::where(function($query) use ($id, $channelLocation) {
            $query->where('channel_id', $id)
                  ->orWhere('from', $channelLocation)
                  ->orWhere('to', $channelLocation);
        })->count();
        
        return view('modules.channel_profile', compact('channel', 'totalItems', 'totalTransfers'));
    }

    public function getTransfers(Request $request, $id) {
        // Get transfers where channel_id matches OR channel appears in from/to location
        $channelLocation = 'channel-' . $id;
        $transfers = Transfer::where(function($query) use ($id, $channelLocation) {
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

    public function getTransferItems(Request $request, $id) {
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
        
        $formattedItems = $transferItems->map(function ($item) {
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
                'created_at' => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : null,
            ];
        });
        
        return response()->json($formattedItems);
    }

    
}
