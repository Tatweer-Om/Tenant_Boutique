<?php

namespace Modules\Color\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Color\Models\Color;

class ColorController extends Controller
{
    public function index()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        return view('color::color');
    }

    public function getcolors()
    {
        return Color::orderBy('id', 'DESC')->paginate(10);
    }

    public function store(Request $request)
    {
        $color = new Color();
        $color->color_name_en = $request->color_name_en;
        $color->color_name_ar = $request->color_name_ar;
        $color->color_code = $request->color_code;
        $color->added_by = 'system';
        $color->user_id = auth()->id() ?? 1;
        $color->save();

        return response()->json($color);
    }

    public function update(Request $request, Color $color)
    {
        $color->color_name_en = $request->color_name_en;
        $color->color_name_ar = $request->color_name_ar;
        $color->color_code = $request->color_code;
        $color->updated_by = 'system_update';
        $color->save();

        return response()->json($color);
    }

    public function show(Color $color)
    {
        return response()->json($color);
    }

    public function destroy(Color $color)
    {
        $color->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
