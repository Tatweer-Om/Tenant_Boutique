<?php

namespace Modules\StockCategory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockCategory\Models\StockCategory;
use Illuminate\Support\Facades\Auth;

class StockCategoryController extends Controller
{
    public function index()
    {
        return view('stockcategory::category');
    }

    public function getCategories()
    {
        return StockCategory::orderBy('id', 'DESC')->paginate(10);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $category = new StockCategory();
        $category->abaya = false; // Default to false
        $category->category_name = $request->category_name;
        $category->category_name_ar = $request->category_name_ar;
        $category->notes = $request->notes;
        $category->added_by = $user->user_name ?? 'system';
        $category->user_id = $user->id ?? 1;

        $category->save();

        return response()->json($category);
    }

    public function update(Request $request, StockCategory $category)
    {
        $user = Auth::guard('tenant')->user();

        $category->category_name = $request->category_name;
        $category->category_name_ar = $request->category_name_ar;
        $category->notes = $request->notes;
        $category->updated_by = $user->user_name ?? 'system_update';
        $category->save();

        return response()->json($category);
    }

    public function show(StockCategory $category)
    {
        return response()->json($category);
    }

    public function destroy(StockCategory $category)
    {
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
