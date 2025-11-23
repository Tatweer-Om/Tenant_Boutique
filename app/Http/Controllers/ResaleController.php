<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Resale;
use App\Models\History;
use App\Models\StockImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ResaleController extends Controller
{
     public function index(){

    return view ('resale.resale');

    }

    public function show_resale()
    {

        $sno=0;

        $view_authresale= Resale::all();
        if(count($view_authresale)>0)
        {
            foreach($view_authresale as $value)
            {

                $item_name='<a class-"patient-info ps-0" href="javascript:void(0);">'.$value->item_name.'</a>';

                $modal = '
                <a href="javascript:void(0);" class="me-3 edit-staff" data-bs-toggle="modal" data-bs-target="#add_resale_modal" onclick=edit("'.$value->id.'")>
                    <i class="fa fa-pencil fs-18 text-success"></i>
                </a>
                <a href="javascript:void(0);" onclick=del("'.$value->id.'")>
                    <i class="fa fa-trash fs-18 text-danger"></i>
                </a>';

                $add_data= Carbon::parse($value->created_at)->format('d-m-Y (h:i a)');




                $sno++;
                $json[] = array(
                    '<span class="patient-info ps-0">'. $sno . '</span>',
                    '<span class="text-nowrap ms-2">' . $item_name . '</span>',
                    '<span class="text-primary">' . $value->unit . '</span>',
                    '<span >' . $value->cost . '</span>',
                     '<span >' . $value->quantity . '</span>',
                      '<span >' . $value->each_quantity . '</span>',
                    '<span >' . $value->added_by . '</span>',
                    '<span >' . $add_data . '</span>',
                    $modal
                );

            }
            $response = array();
            $response['success'] = true;
            $response['aaData'] = $json;
            echo json_encode($response);
        }
        else
        {
            $response = array();
            $response['sEcho'] = 0;
            $response['iTotalRecords'] = 0;
            $response['iTotalDisplayRecords'] = 0;
            $response['aaData'] = [];
            echo json_encode($response);
        }
    }

    public function add_resale(Request $request){

        $user_id = Auth::id();
        $data= User::where('id', $user_id )->first();
        $user_name= $data->user_name;

  

        $cover_image = "";

        if ($request->hasFile('cover_image')) {
            $folderPath = public_path('images/cover_images');

            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

            $cover_image = time() . '.' . $request->file('cover_image')->extension();
            $request->file('cover_image')->move($folderPath, $cover_image);
        }


        $resale = new resale();

$resale->name = $request['name'];
$resale->item_code = $request['item_code'];
$resale->cost = $request['cost'];
$resale->sale_price = $request['sale_price'];
$resale->discounted_price = $request['discounted_price'];
$resale->cost_of_tailor = $request['cost_of_tailor'];
$resale->product_type = $request['product_type'];
$resale->each_quantity = $request['each_quantity'];
$resale->tailor_name = json_encode($request['tailor_name']);
$resale->notes = $request['notes'];
$resale->added_by = $user_name;
$resale->user_id = $user_id;
$resale->save();
return response()->json(['resale_id' => $resale->id]);

    }


    public function edit_resale(Request $request){

        $resale_id = $request->input('id');

        $resale_data = Resale::where('id', $resale_id)->first();

                $cover_image = $resale_data->cover_image
            ? asset('images/cover_images/' . $resale_data->cover_image)
            : asset('images/dummy_images/cover-image-icon.png');
       
        $data = [
            'resale_id' => $resale_data->id,
            'name' => $resale_data->name,
            'item_code' => $resale_data->item_code,
            'cost' => $resale_data->cost,
            'sale_price' => $resale_data->sale_price,
              'cover_image' => $cover_image,
            'discounted_price' => $resale_data->discounted_price,
            'cost_of_tailor' => $resale_data->cost_of_tailor,
            'product_type' => $resale_data->product_type,
            'each_quantity' => $resale_data->each_quantity,
            'tailor_name' => json_decode($resale_data->tailor_name, true),
            'cover_pic' => $resale_data->cover_pic,
            'multi_pic' => json_decode($resale_data->multi_pic, true),
            'notes' => $resale_data->notes,
        ];

        return response()->json($data);
    }

    public function update_resale(Request $request)
{
    $resale_id = $request->input('resale_id');
    $user_id = Auth::id();

    $user = User::where('id', $user_id)->first();
    $user_name = $user->user_name;

    $resale = Resale::where('id', $resale_id)->first();

    if (!$resale) {
        return response()->json(['error' => trans('messages.resale_not_found', [], session('locale'))], 404);
    }

      $resale_image =$resale->cover_image;

        if ($request->hasFile('cover_image')) {
            $oldImagePath = public_path('images/cover_images/' .$resale->cover_image);
            if (File::exists($oldImagePath)) {
                File::delete($oldImagePath);
            }

            $folderPath = public_path('images/cover_images');
            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

           $resale_image = time() . '.' . $request->file('cover_image')->extension();
            $request->file('cover_image')->move($folderPath,$resale_image);
        }

    $previousData = $resale->only([
        'name', 
        'item_code', 
        'cost', 
        'sale_price', 
        'discounted_price', 
        'cost_of_tailor', 
        'product_type', 
        'each_quantity', 
        'tailor_name', 
        'resale_image', 
        'multi_pic', 
        'notes', 
        'added_by', 
        'user_id', 
        'created_at'
    ]);

    $resale->name = $request->input('name');
    $resale->item_code = $request->input('item_code');
    $resale->cost = $request->input('cost');
      $user->cover_image = $resale_image;
    $resale->sale_price = $request->input('sale_price');
    $resale->discounted_price = $request->input('discounted_price');
    $resale->cost_of_tailor = $request->input('cost_of_tailor');
    $resale->product_type = $request->input('product_type');
    $resale->each_quantity = $request->input('each_quantity');
    $resale->tailor_name = json_encode($request->input('tailor_name'));
    $resale->notes = $request->input('notes');
    $resale->added_by = $user_name;
    $resale->user_id = $user_id;
    $resale->save();

    $history = new History();
    $history->user_id = $user_id;
    $history->table_name = 'resalees';
    $history->function = 'update';
    $history->function_status = 1;
    $history->resale_id = $resale_id;
    $history->record_id = $resale->id;
    $history->previous_data = json_encode($previousData);
    $history->updated_data = json_encode($resale->only([
          'name', 
        'item_code', 
        'cost', 
        'sale_price', 
        'discounted_price', 
        'cost_of_tailor', 
        'product_type', 
        'each_quantity', 
        'tailor_name', 
        'cover_pic', 
        'multi_pic', 
        'notes', 
        'added_by', 
        'user_id', 
        'created_at'
    ]));
    $history->added_by = $user_name;
    $history->save();

    return response()->json([trans('messages.success_lang', [], session('locale')) => trans('messages.user_update_lang', [], session('locale'))]);
}


public function delete_resale(Request $request) {


    $user_id = Auth::id();
    $user = User::where('id', $user_id)->first();
    $user_name = $user->user_name;
    $resale_id = $request->input('id');
    $resale = Resale::where('id', $resale_id)->first();

    if (!$resale) {
        return response()->json([trans('messages.error_lang', [], session('locale')) => trans('messages.resale_not_found', [], session('locale'))], 404);
    }

    $previousData = $resale->only([
           'name', 
        'item_code', 
        'cost', 
        'sale_price', 
        'discounted_price', 
        'cost_of_tailor', 
        'product_type', 
        'each_quantity', 
        'tailor_name', 
        'cover_pic', 
        'multi_pic', 
        'notes', 
        'added_by', 
        'user_id', 
        'created_at'
    ]);

    $currentUser = Auth::user();
    $username = $currentUser->user_name;
    $resale_id = $currentUser->resale_id;

    $history = new History();
    $history->user_id = $user_id;
    $history->table_name = 'resalees';
    $history->function = 'delete';
    $history->function_status = 2;
    $history->resale_id = $resale_id;
    $history->record_id = $resale->id;
    $history->previous_data = json_encode($previousData);

    $history->added_by = $user_name;
    $history->save();
    $resale->delete();

    return response()->json([
        trans('messages.success_lang', [], session('locale')) => trans('messages.user_deleted_lang', [], session('locale'))
    ]);
}




public function get_images(Request $request)
{
    $user = Auth::user();
    $id = $user->id;
    $folderPath = public_path('images/temp_' . $id);

    if (!File::isDirectory($folderPath)) {
        File::makeDirectory($folderPath, 0777, true, true);
    }

    if ($request->hasFile('stock_image')) {
        $i = 0;
        foreach ($request->file('stock_image') as $file) {
            $filename = time() . $i . '_' . $file->getClientOriginalName();
            $file->move($folderPath, $filename);
            $i++;
        }
    }

    return response()->json(['message' => 'Files uploaded successfully.']);
}

public function show_images(Request $request)
{
    $user = Auth::user();
    $id = $user->id;
    $folderPath = public_path('images/temp_' . $id);

    $images = [];
    if (File::isDirectory($folderPath)) {
        $images = File::files($folderPath);
        $images = array_map(function ($file) {
            return $file->getFilename();
        }, $images);
    }

    return response()->json([
        'user_id' => $id,
        'images' => $images
    ]);
}

public function upload_img(Request $request)
{
    $id = $request->input('stock_id');
    $filename = "";

    if ($request->hasFile('stock_image')) {
        $folderPath = public_path('images/stock_images');

        if (!File::isDirectory($folderPath)) {
            File::makeDirectory($folderPath, 0777, true, true);
        }

        $filename = time() . '.' . $request->file('stock_image')->extension();
        $request->file('stock_image')->move($folderPath, $filename);
    }

    $stockImage = new StockImage();
    $stockImage->image_path = $filename;
    $stockImage->stock_id = $id;
    $stockImage->save();

    $stock_images = StockImage::where('stock_id', $id)->get();
    $images = '';
    foreach ($stock_images as $img)
        $images .= ' <div class="col-lg-2" data-filename="' . $img->image_path . '">
            <img src="' . asset('images/stock_images/' . $img->image_path) . '" class="m-2 img-fluid" style="width: 100px !important; height: 50px !important;" />
            <button type="button" class="delete-img btn btn-link p-0" onclick=del_stock_image("' . $img->id . '") style="background: none; border: none; cursor: pointer;">x</button>
        </div>';

    return response()->json(['images' => $images]);
}

public function del_img(Request $request)
{
    $id = $request->input('id');
    $stockImage = StockImage::where('id', $id)->first();

    if (!$stockImage) {
        return response()->json(['message' => 'Image not found'], 404);
    }

    $stock_id = $stockImage->stock_id;
    $filePath = public_path('images/stock_images/' . $stockImage->image_path);

    if (File::exists($filePath)) {
        File::delete($filePath);
    }

    $stockImage->delete();

    $stock_images = StockImage::where('stock_id', $stock_id)->get();
    $images = '';
    foreach ($stock_images as $img)
        $images .= ' <div class="col-lg-2" data-filename="' . $img->image_path . '">
            <img src="' . asset('images/stock_images/' . $img->image_path) . '" class="m-2 img-fluid" style="width: 100px !important; height: 50px !important;" />
            <button type="button" class="delete-img btn btn-link p-0" onclick=del_stock_image("' . $img->id . '") style="background: none; border: none; cursor: pointer;">x</button>
        </div>';

    return response()->json(['message' => 'File deleted successfully.', 'images' => $images]);
}

public function delete_image(Request $request)
{
    $user = Auth::user();
    $id = $user->id;
    $filename = $request->input('filename');
    $filePath = public_path('images/temp_' . $id . '/' . $filename);

    if (File::exists($filePath)) {
        if (File::delete($filePath)) {
            return response()->json(['message' => 'File deleted successfully.']);
        } else {
            return response()->json(['message' => 'Failed to delete file.'], 500);
        }
    } else {
        return response()->json(['message' => 'File not found.'], 404);
    }
}

}
