<?php

namespace Modules\Size\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Size\Models\Size;

class SizeController extends Controller
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

        return view('size::size');
    }

    public function getSizes()
    {
        return Size::orderBy('id', 'DESC')->paginate(10);
    }

    public function store(Request $request)
    {
        $size = new Size();
        $size->size_name_en = $request->size_name_en;
        $size->size_name_ar = $request->size_name_ar;
        $size->size_code_en = $request->size_code_en;
        $size->size_code_ar = $request->size_code_ar;
        $size->added_by = 'system';
        $size->user_id = auth()->id() ?? 1;
        $size->save();

        return response()->json($size);
    }

    public function update(Request $request, Size $size)
    {
        $size->size_name_en = $request->size_name_en;
        $size->size_name_ar = $request->size_name_ar;
        $size->size_code_en = $request->size_code_en;
        $size->size_code_ar = $request->size_code_ar;
        $size->updated_by = 'system_update';
        $size->save();

        return response()->json($size);
    }

    public function show(Size $size)
    {
        return response()->json($size);
    }

    public function destroy(Size $size)
    {
        $size->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
