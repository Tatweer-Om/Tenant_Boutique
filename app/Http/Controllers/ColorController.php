<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ColorController extends Controller
{
    
public function index() {
        return view('modules.color');
    }

public function getcolors() {
    return Color::orderBy('id', 'DESC')->paginate(10);
}


 public function store(Request $request)
{
        $color = new Color();
        $color->color_name_en = $request->color_name_en;
        $color->color_name_ar = $request->color_name_ar;
        $color->color_code = $request->color_code;
        $color->added_by = 'system';          
        $color->user_id = 1;
    

    $color->save();

    return response()->json($color);
}

    public function update(Request $request, color $color) {

    $color->color_name_en = $request->color_name_en;
    $color->color_name_ar = $request->color_name_ar;
    $color->color_code = $request->color_code;
    $color->updated_by = 'system_update';
    $color->save();

    return response()->json($color);
    }
    public function show(color $color) {
    return response()->json($color);
}

    public function destroy(color $color) {
        $color->delete();
        return response()->json(['message' => 'Deleted']);
    }

}
