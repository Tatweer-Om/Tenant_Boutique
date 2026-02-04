<?php

namespace Modules\Area\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AreaController extends Controller
{
    public function index()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }
// hahah
        return view('area::area');
    }

    public function getAreas()
    {
        return Area::orderBy('id', 'DESC')->paginate(10);
    }

    /**
     * Return all areas (for dropdowns)
     */
    public function all()
    {
        return response()->json(
            Area::orderBy('area_name_ar', 'ASC')
                ->get(['id', 'area_name_en', 'area_name_ar'])
        );
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $area = new Area();
        $area->area_name_en = $request->area_name_en;
        $area->area_name_ar = $request->area_name_ar;
        $area->notes = $request->notes;
        $area->added_by = $user->name ?? $user->user_name ?? 'system';
        $area->user_id = $user->id ?? null;

        $area->save();

        return response()->json($area);
    }

    public function update(Request $request, Area $area)
    {
        $user = Auth::guard('tenant')->user();

        $area->area_name_en = $request->area_name_en;
        $area->area_name_ar = $request->area_name_ar;
        $area->notes = $request->notes;
        $area->updated_by = $user->name ?? $user->user_name ?? 'system_update';
        $area->save();

        return response()->json($area);
    }

    public function show(Area $area)
    {
        return response()->json($area);
    }

    public function destroy(Area $area)
    {
        $area->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
