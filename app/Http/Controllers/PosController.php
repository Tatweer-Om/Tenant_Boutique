<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Stock;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index(){
        // Fetch all categories
        $categories = Category::orderBy('id', 'ASC')->get();
        
        // Fetch all stocks (abayas) with their images and category
        $stocks = Stock::with(['images', 'category'])
            ->whereNotNull('category_id')
            ->orderBy('id', 'DESC')
            ->get();
        
        return view('pos.pos_page', compact('categories', 'stocks'));
    }

    public function getStockDetails($id)
    {
        $stock = Stock::with([
            'colorSizes.size',
            'colorSizes.color',
            'images',
            'category'
        ])->findOrFail($id);

        $colorSizes = [];
        foreach ($stock->colorSizes as $item) {
            $sizeName = session('locale') === 'ar' 
                ? ($item->size?->size_name_ar ?? '-') 
                : ($item->size?->size_name_en ?? '-');
            
            $colorName = session('locale') === 'ar' 
                ? ($item->color?->color_name_ar ?? '-') 
                : ($item->color?->color_name_en ?? '-');
            
            $colorSizes[] = [
                'size_id' => $item->size_id,
                'size_name' => $sizeName,
                'color_id' => $item->color_id,
                'color_name' => $colorName,
                'color_code' => $item->color?->color_code ?? '#000000',
                'quantity' => $item->qty ?? 0,
            ];
        }

        return response()->json([
            'id' => $stock->id,
            'name' => session('locale') === 'ar' && $stock->design_name ? $stock->design_name : ($stock->design_name ?: $stock->abaya_code),
            'abaya_code' => $stock->abaya_code,
            'price' => $stock->sales_price ?? 0,
            'image' => $stock->images->first() ? asset($stock->images->first()->image_path) : null,
            'colorSizes' => $colorSizes,
        ]);
    }
}
