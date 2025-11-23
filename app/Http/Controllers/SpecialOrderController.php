<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Stock;
use App\Models\SpecialOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SpecialOrderController extends Controller
{
       public function index(){

    return view ('specialorder.specialorder');

    }

  public function show_specialorder()
{
    $sno = 0;

    $view_authspecialorder = SpecialOrder::with('items')->get(); // eager load items
    $json = [];

    if ($view_authspecialorder->count() > 0) {
        foreach ($view_authspecialorder as $order) {

            // Format created date
            $add_data = Carbon::parse($order->created_at)->format('d-m-Y (h:i a)');

            // Modal actions
            $modal = '
            <a href="javascript:void(0);" class="me-3 edit-staff" data-bs-toggle="modal" data-bs-target="#add_specialorder_modal" onclick=edit("'.$order->id.'")>
                <i class="fa fa-pencil fs-18 text-success"></i>
            </a>
            <a href="javascript:void(0);" onclick=del("'.$order->id.'")>
                <i class="fa fa-trash fs-18 text-danger"></i>
            </a>';

            // Increment serial number
            $sno++;

            // Loop through items and combine their info into a string
            $items_info = '';
            foreach ($order->items as $item) {
                $items_info .= '<div class="mb-1">';
                $items_info .= '<strong>'.$item->item_name.'</strong>';
                $items_info .= ' | Abaya Length: '.$item->abaya_length;
                $items_info .= ' | Bust: '.$item->bust;
                $items_info .= ' | Sleeves: '.$item->sleeves_length;
                $items_info .= ' | Buttons: '.($item->buttons ? 'Yes' : 'No');
                $items_info .= ' | Notes: '.$item->notes;
                $items_info .= '</div>';
            }

            $json[] = [
                '<span class="patient-info ps-0">'. $sno . '</span>',
                '<span class="text-nowrap ms-2">' . $order->customer_name . '</span>',
                '<span >' . $order->source . '</span>',
                '<span >' . ($order->send_as_gift ? 'Yes' : 'No') . '</span>',
                '<span >' . $order->gift_text . '</span>',
                '<span >' . $order->notes . '</span>',
                '<span >' . $order->added_by . '</span>',
                '<span >' . $add_data . '</span>',
                '<span>' . $items_info . '</span>',
                $modal
            ];
        }

        $response = [
            'success' => true,
            'aaData' => $json,
        ];
        echo json_encode($response);

    } else {
        $response = [
            'sEcho' => 0,
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'aaData' => [],
        ];
        echo json_encode($response);
    }
}


 public function add_specialorder(Request $request)
{
    $user_id = Auth::id();
    $user = User::find($user_id);
    $user_name = $user->user_name;

    // Create master order
    $specialorder = new SpecialOrder();
    $specialorder->source = $request['source'];
    $specialorder->customer_name = $request['name'];
    $specialorder->contact = $request['contact'];
    $specialorder->city = $request['city'];
    $specialorder->area = $request['area'];
    $specialorder->send_as_gift = $request['send_as_gift'] ?? 0;
    $specialorder->gift_text = $request['gift_text'] ?? null;
    $specialorder->notes = $request['notes'] ?? null;
    $specialorder->added_by = $user_name;
    $specialorder->user_id = $user_id;
    $specialorder->save();

    // Save multiple dresses
    foreach ($request->items as $item) {
        $specialorder_item = new Stock();
        $specialorder_item->special_order_id = $specialorder->id;
        $specialorder_item->item_name = $item['item_name'];
        $specialorder_item->abaya_length = $item['abaya_length'] ?? null;
        $specialorder_item->bust = $item['bust'] ?? null;
        $specialorder_item->sleeves_length = $item['sleeves_length'] ?? null;
        $specialorder_item->buttons = $item['buttons'] ?? 0;
        $specialorder_item->notes = $item['notes'] ?? null;
        $specialorder_item->save();
    }

    return response()->json(['specialorder_id' => $specialorder->id]);
}


  public function edit_specialorder(Request $request)
{
    $specialorder_id = $request->input('id');
    $specialorder = SpecialOrder::with('items')->find($specialorder_id);

    if (!$specialorder) {
        return response()->json(['error' => 'Special order not found'], 404);
    }

    $data = [
        'specialorder_id' => $specialorder->id,
        'source' => $specialorder->source,
        'name' => $specialorder->customer_name,
        'contact' => $specialorder->contact,
        'city' => $specialorder->city,
        'area' => $specialorder->area,
        'send_as_gift' => $specialorder->send_as_gift,
        'gift_text' => $specialorder->gift_text,
        'notes' => $specialorder->notes,
        'items' => $specialorder->items, // Array of dresses
    ];

    return response()->json($data);
}


public function update_specialorder(Request $request)
{
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name;
    $specialorder_id = $request->input('specialorder_id');

    $specialorder = SpecialOrder::find($specialorder_id);
    if (!$specialorder) {
        return response()->json(['error' => 'Special order not found'], 404);
    }

    $previousData = $specialorder->toArray();
    $specialorder->source = $request['source'];
    $specialorder->customer_name = $request['name'];
    $specialorder->contact = $request['contact'];
    $specialorder->city = $request['city'];
    $specialorder->area = $request['area'];
    $specialorder->send_as_gift = $request['send_as_gift'] ?? 0;
    $specialorder->gift_text = $request['gift_text'] ?? null;
    $specialorder->notes = $request['notes'] ?? null;
    $specialorder->added_by = $user_name;
    $specialorder->user_id = $user_id;
    $specialorder->save();

    // Delete old items and insert new ones
    $specialorder->items()->delete();
    foreach ($request->items as $item) {
        $specialorder_item = new Stock();
        $specialorder_item->special_order_id = $specialorder->id;
        $specialorder_item->item_name = $item['item_name'];
        $specialorder_item->abaya_length = $item['abaya_length'] ?? null;
        $specialorder_item->bust = $item['bust'] ?? null;
        $specialorder_item->sleeves_length = $item['sleeves_length'] ?? null;
        $specialorder_item->buttons = $item['buttons'] ?? 0;
        $specialorder_item->notes = $item['notes'] ?? null;
        $specialorder_item->save();
    }

    // You can add history logging here following your previous pattern

    return response()->json(['message' => 'Special order updated successfully']);
}



public function delete_specialorder(Request $request)
{
    $user_id = Auth::id();
    $user_name = Auth::user()->user_name;
    $specialorder_id = $request->input('id');

    $specialorder = SpecialOrder::find($specialorder_id);
    if (!$specialorder) {
        return response()->json(['error' => 'Special order not found'], 404);
    }

    // Delete related items first
    $specialorder->items()->delete();

    // Add history logging here if needed

    $specialorder->delete();

    return response()->json(['message' => 'Special order deleted successfully']);
}

}
