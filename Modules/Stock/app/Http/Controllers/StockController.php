<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ColorSize;
use App\Models\Stock;
use App\Models\StockColor;
use App\Models\StockHistory;
use App\Models\StockImage;
use App\Models\StockSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\User\Models\User;

class StockController extends Controller
{
    public function view_stock()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        return view('stock::view_stock');
    }

    public function add_stock_form()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];
        if (!in_array(9, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        $colors = \Modules\Color\Models\Color::all();
        $sizes = \Modules\Size\Models\Size::all();
        $categories = Category::all();

        return view('stock::add_stock', compact('colors', 'sizes', 'categories'));
    }

    public function add_stock(Request $request)
    {
        if (!$request->category_id) {
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.enter_category', [], session('locale')) ?: 'Please select a category'
            ], 422);
        }

        $hasColorSize = false;
        if ($request->filled('color_sizes') && is_array($request->color_sizes)) {
            foreach ($request->color_sizes as $color_id => $sizes) {
                if (!empty($sizes) && is_array($sizes)) {
                    foreach ($sizes as $size_id => $data) {
                        if (!empty($color_id) && !empty($size_id) && isset($data['qty']) && floatval($data['qty']) > 0) {
                            $hasColorSize = true;
                            break 2;
                        }
                    }
                }
            }
        }
        if (!$hasColorSize && !empty($request->colors)) {
            foreach ($request->colors as $c) {
                if (!empty($c['color_id']) && (floatval($c['qty'] ?? 0) > 0)) {
                    $hasColorSize = true;
                    break;
                }
            }
        }
        if (!$hasColorSize && !empty($request->sizes)) {
            foreach ($request->sizes as $size_id => $s) {
                if (!empty($size_id) && (floatval($s['qty'] ?? 0) > 0)) {
                    $hasColorSize = true;
                    break;
                }
            }
        }

        if (!$hasColorSize) {
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.enter_color_size', [], session('locale')) ?: 'Please add at least one color and size combination'
            ], 422);
        }

        $totalQty = 0;
        if (!empty($request->color_sizes)) {
            foreach ($request->color_sizes as $color_id => $sizes) {
                foreach ($sizes as $size_id => $data) {
                    $totalQty += (float)($data['qty'] ?? 0);
                }
            }
        }
        if (!empty($request->colors)) {
            foreach ($request->colors as $c) {
                $totalQty += (float)($c['qty'] ?? 0);
            }
        }
        if (!empty($request->sizes)) {
            foreach ($request->sizes as $s) {
                $totalQty += (float)($s['qty'] ?? 0);
            }
        }

        $stock = new Stock();
        $stock->abaya_code = $request->abaya_code;
        $stock->design_name = $request->design_name;
        $stock->barcode = $request->barcode;
        $stock->abaya_notes = $request->abaya_notes ?? '';
        $stock->category_id = $request->category_id;
        $stock->cost_price = $request->cost_price ?? 0;
        $stock->sales_price = $request->sales_price ?? 0;
        $stock->tailor_charges = $request->tailor_charges ?? 0;
        $stock->tailor_id = null;
        $stock->quantity = $totalQty;
        $stock->notification_limit = $request->notification_limit ?? null;
        $stock->mode = $request->mode ?? 'color_size';
        $stock->website_data_delivery_status = 1;
        $stock->save();

        $user = Auth::guard('tenant')->user();
        $user_id = $user?->id;
        $user_name = $user?->user_name ?? 'System';

        if (!empty($request->colors)) {
            foreach ($request->colors as $color) {
                $qty = (float)($color['qty'] ?? 0);
                $stockColor = new StockColor();
                $stockColor->stock_id = $stock->id;
                $stockColor->color_id = $color['color_id'];
                $stockColor->qty = $qty;
                $stockColor->save();

                if ($qty > 0) {
                    StockHistory::create([
                        'stock_id' => $stock->id,
                        'size_id' => null,
                        'color_id' => $color['color_id'],
                        'old_qty' => 0,
                        'changed_qty' => $qty,
                        'new_qty' => $qty,
                        'action_type' => 1,
                        'tailor_id' => null,
                        'pull_notes' => null,
                        'user_id' => $user_id ? (string)$user_id : null,
                        'added_by' => $user_name,
                    ]);
                }
            }
        }

        if (!empty($request->sizes)) {
            foreach ($request->sizes as $size_id => $size) {
                $qty = (float)($size['qty'] ?? 0);
                $stockSize = new StockSize();
                $stockSize->stock_id = $stock->id;
                $stockSize->size_id = is_numeric($size_id) ? $size_id : ($size['size_id'] ?? $size_id);
                $stockSize->qty = $qty;
                $stockSize->save();

                if ($qty > 0) {
                    StockHistory::create([
                        'stock_id' => $stock->id,
                        'size_id' => $stockSize->size_id,
                        'color_id' => null,
                        'old_qty' => 0,
                        'changed_qty' => $qty,
                        'new_qty' => $qty,
                        'action_type' => 1,
                        'tailor_id' => null,
                        'pull_notes' => null,
                        'user_id' => $user_id ? (string)$user_id : null,
                        'added_by' => $user_name,
                    ]);
                }
            }
        }

        if (!empty($request->color_sizes)) {
            foreach ($request->color_sizes as $color_id => $sizes) {
                foreach ($sizes as $size_id => $data) {
                    $qty = (float)($data['qty'] ?? 0);
                    $colorSize = new ColorSize();
                    $colorSize->stock_id = $stock->id;
                    $colorSize->color_id = $color_id;
                    $colorSize->size_id = $data['size_id'] ?? $size_id;
                    $colorSize->qty = $qty;
                    $colorSize->save();

                    if ($qty > 0) {
                        StockHistory::create([
                            'stock_id' => $stock->id,
                            'size_id' => $colorSize->size_id,
                            'color_id' => $color_id,
                            'old_qty' => 0,
                            'changed_qty' => $qty,
                            'new_qty' => $qty,
                            'action_type' => 1,
                            'tailor_id' => null,
                            'pull_notes' => null,
                            'user_id' => $user_id ? (string)$user_id : null,
                            'added_by' => $user_name,
                        ]);
                    }
                }
            }
        }

        if ($request->hasFile('images')) {
            $folderPath = public_path('images/stock_images');
            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . Str::random(6) . '.' . $image->getClientOriginalExtension();
                $image->move($folderPath, $imageName);
                $stock_img = new StockImage();
                $stock_img->stock_id = $stock->id;
                $stock_img->image_path = 'images/stock_images/' . $imageName;
                $stock_img->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.stock_added_successfully', [], session('locale')) ?: 'Stock added successfully!',
            'redirect_url' => url('view_stock'),
        ]);
    }

    public function getstock()
    {
        $stocks = Stock::with([
            'colorSizes.size',
            'colorSizes.color',
            'images',
            'category'
        ])
            ->orderBy('id', 'DESC')
            ->paginate(10);

        return response()->json($stocks);
    }

    public function edit_stock($id)
    {
        $colors = \Modules\Color\Models\Color::all();
        $sizes = \Modules\Size\Models\Size::all();
        $categories = Category::all();

        $stock = Stock::with([
            'colors',
            'sizes',
            'colorSizes',
            'images',
            'category'
        ])->findOrFail($id);

        $returnPage = (int) request()->get('page', 1);
        if ($returnPage < 1) {
            $returnPage = 1;
        }

        return view('stock::edit_stock', compact('colors', 'sizes', 'categories', 'id', 'stock', 'returnPage'));
    }

    public function deleteImage($id)
    {
        $image = StockImage::findOrFail($id);

        if (file_exists(public_path($image->image_path))) {
            unlink(public_path($image->image_path));
        }

        $image->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Image deleted successfully!'
        ]);
    }

    public function update_stock(Request $request)
    {
        $stock_id = $request->stock_id;
        $stock = Stock::findOrFail($stock_id);

        $stock->abaya_code = $request->abaya_code;
        $stock->design_name = $request->design_name;
        $stock->barcode = $request->barcode;
        $stock->abaya_notes = $request->abaya_notes;
        $stock->category_id = $request->category_id;
        $stock->cost_price = $request->cost_price;
        $stock->sales_price = $request->sales_price;
        $stock->tailor_charges = $request->tailor_charges ?? 0;
        $stock->website_data_delivery_status = 1;
        $stock->tailor_id = null;

        $totalQty = 0;
        if (!empty($request->colors)) {
            foreach ($request->colors as $c) {
                $totalQty += (float)($c['qty'] ?? 0);
            }
        }
        if (!empty($request->sizes)) {
            foreach ($request->sizes as $s) {
                $totalQty += (float)($s['qty'] ?? 0);
            }
        }
        if (!empty($request->color_sizes)) {
            foreach ($request->color_sizes as $color_id => $sizes) {
                if (is_array($sizes)) {
                    foreach ($sizes as $size_id => $data) {
                        $totalQty += (float)($data['qty'] ?? 0);
                    }
                }
            }
        }
        $stock->quantity = $totalQty;
        $stock->notification_limit = $request->notification_limit;
        $stock->mode = $request->mode;
        $stock->save();

        StockColor::where('stock_id', $stock_id)->delete();
        StockSize::where('stock_id', $stock_id)->delete();
        ColorSize::where('stock_id', $stock_id)->delete();

        if (!empty($request->colors)) {
            foreach ($request->colors as $color) {
                $stockColor = new StockColor();
                $stockColor->stock_id = $stock->id;
                $stockColor->color_id = $color['color_id'];
                $stockColor->qty = $color['qty'] ?? 0;
                $stockColor->save();
            }
        }

        if (!empty($request->sizes)) {
            foreach ($request->sizes as $size_id => $size) {
                $stockSize = new StockSize();
                $stockSize->stock_id = $stock->id;
                $stockSize->size_id = $size_id;
                $stockSize->qty = $size['qty'] ?? 0;
                $stockSize->save();
            }
        }

        if (!empty($request->color_sizes)) {
            foreach ($request->color_sizes as $color_id => $sizes) {
                foreach ($sizes as $size_id => $data) {
                    $colorSize = new ColorSize();
                    $colorSize->stock_id = $stock->id;
                    $colorSize->color_id = $color_id;
                    $colorSize->size_id = $data['size_id'];
                    $colorSize->qty = $data['qty'] ?? 0;
                    $colorSize->save();
                }
            }
        }

        if ($request->hasFile('images')) {
            $folderPath = public_path('images/stock_images');
            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . Str::random(6) . '.' . $image->getClientOriginalExtension();
                $image->move($folderPath, $imageName);
                $stock_img = new StockImage();
                $stock_img->stock_id = $stock->id;
                $stock_img->image_path = 'images/stock_images/' . $imageName;
                $stock_img->save();
            }
        }

        $returnPage = (int) $request->get('return_page', 1);
        $redirectUrl = url('view_stock');
        if ($returnPage > 1) {
            $redirectUrl .= '?page=' . $returnPage;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Stock Updated successfully!',
            'redirect_url' => $redirectUrl,
        ]);
    }

    public function delete_stock($id)
    {
        $stock = Stock::find($id);

        if (!$stock) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stock not found'
            ], 404);
        }

        StockColor::where('stock_id', $id)->delete();
        StockSize::where('stock_id', $id)->delete();
        ColorSize::where('stock_id', $id)->delete();
        StockImage::where('stock_id', $id)->delete();
        $stock->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Stock deleted successfully!'
        ]);
    }

    public function stock_detail(Request $request)
    {
        $stock = Stock::with(['colors.color', 'sizes.size', 'images', 'colorSizes.size', 'colorSizes.color'])
            ->findOrFail($request->id);

        $stock_sizes = $stock->sizes;
        $stock_colors = $stock->colors;
        $stock_sizescolor = $stock->colorSizes;

        $htmlSizeColor = '';
        foreach ($stock_sizescolor as $item) {
            $size_name = session('locale') === 'ar'
                ? ($item->size?->size_name_ar ?? '-')
                : ($item->size?->size_name_en ?? '-');
            $color_name = session('locale') === 'ar'
                ? ($item->color?->color_name_ar ?? '-')
                : ($item->color?->color_name_en ?? '-');
            $color_code = $item->color?->color_code ?? '#000';
            $qty = $item->qty ?? 0;
            $htmlSizeColor .= '<div class="flex justify-between items-center border rounded-lg p-3 bg-gray-50 text-xs sm:text-sm">
                <div class="flex flex-col">
                    <span class="font-semibold">Size: ' . $size_name . '</span>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="w-4 h-4 rounded-full border" style="background:' . $color_code . '"></span>
                        <span>' . $color_name . '</span>
                    </div>
                </div>
                <span class="font-bold text-[var(--primary-color)]">' . $qty . ' pcs</span>
            </div>';
        }

        $html = '';
        $color = '';
        foreach ($stock_colors as $stock_color) {
            $color_name = session('locale') === 'ar'
                ? $stock_color->color->color_name_ar
                : $stock_color->color->color_name_en;
            $color_code = $stock_color->color->color_code ?? '#000';
            $color_qty = $stock_color->qty;
            $color .= '<div class="flex items-center justify-between border rounded-lg p-3 bg-gray-50 text-xs sm:text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full border" style="background:' . $color_code . '"></span>
                    <span class="font-semibold">' . $color_name . '</span>
                </div>
                <span class="font-bold text-[var(--primary-color)]">' . $color_qty . ' pcs</span>
            </div>';
        }

        foreach ($stock_sizes as $index => $stock_size) {
            $size_name = session('locale') === 'ar'
                ? $stock_size->size->size_name_ar
                : $stock_size->size->size_name_en;
            $size_qty = $stock_size->qty;
            $html .= '<div class="p-3 border rounded-lg bg-gray-50 text-center font-bold text-gray-700 text-xs sm:text-sm">
                <span>' . $size_name . '</span>
                <span class="block text-[var(--primary-color)] mt-1">' . $size_qty . ' pcs</span>
            </div>';
        }

        return response()->json([
            'stock_id' => $stock->id,
            'abaya_code' => $stock->abaya_code,
            'abaya_notes' => $stock->abaya_notes,
            'design_name' => $stock->design_name,
            'image_path' => $stock->images->first() ? $stock->images->first()->image_path : null,
            'barcode' => $stock->barcode,
            'status' => 'Available',
            'sizes_html' => $html,
            'size_color_html' => $htmlSizeColor,
            'color' => $color,
        ]);
    }

    public function get_full_stock_details(Request $request)
    {
        $locale = session('locale');
        $stock = Stock::with([
            'colorSizes.size',
            'colorSizes.color',
            'images'
        ])->findOrFail($request->id);

        $totalQuantity = 0;
        $colorSizeDetails = [];

        foreach ($stock->colorSizes as $item) {
            $size_name = $locale === 'ar'
                ? ($item->size?->size_name_ar ?? '-')
                : ($item->size?->size_name_en ?? '-');
            $color_name = $locale === 'ar'
                ? ($item->color?->color_name_ar ?? '-')
                : ($item->color?->color_name_en ?? '-');
            $qty = (int)($item->qty ?? 0);
            $totalQuantity += $qty;
            $colorSizeDetails[] = [
                'size_name' => $size_name,
                'color_name' => $color_name,
                'color_code' => $item->color?->color_code ?? '#000000',
                'quantity' => $qty,
            ];
        }

        $images = $stock->images->map(fn($img) => $img->image_path)->toArray();

        return response()->json([
            'stock_id' => $stock->id,
            'abaya_code' => $stock->abaya_code ?? '-',
            'design_name' => $stock->design_name ?? '-',
            'barcode' => $stock->barcode ?? '-',
            'abaya_notes' => $stock->abaya_notes ?? '-',
            'cost_price' => $stock->cost_price ?? 0,
            'sales_price' => $stock->sales_price ?? 0,
            'tailor_charges' => $stock->tailor_charges ?? 0,
            'tailor_names' => [],
            'total_quantity' => $totalQuantity,
            'images' => $images,
            'color_size_details' => $colorSizeDetails,
        ]);
    }

    public function get_stock_quantity(Request $request)
    {
        $id = $request->id ?? null;
        $stock = Stock::with(['colors.color', 'sizes.size', 'images', 'colorSizes.size', 'colorSizes.color'])
            ->findOrFail($id);

        $stock_sizes = $stock->sizes;
        $stock_colors = $stock->colors;
        $stock_sizescolor = $stock->colorSizes;

        $htmlSizeColor = '<div class="row g-4">';
        foreach ($stock_sizescolor as $item) {
            $size_name = session('locale') === 'ar'
                ? ($item->size?->size_name_ar ?? '-')
                : ($item->size?->size_name_en ?? '-');
            $color_name = session('locale') === 'ar'
                ? ($item->color?->color_name_ar ?? '-')
                : ($item->color?->color_name_en ?? '-');
            $color_code = $item->color?->color_code ?? '#000';
            $qty = $item->qty ?? 0;

            $htmlSizeColor .= '
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <div class="rounded-circle border border-2 shadow-sm"
                                style="width:18px;height:18px;background-color:' . htmlspecialchars($color_code) . ';"></div>
                            <span class="fw-semibold text-secondary" style="font-size: 0.8rem;">' . htmlspecialchars($color_name) . '</span>
                        </div>
                        <div class="mb-2 text-center">
                            <span class="badge bg-dark text-light px-2 py-1 rounded-pill" style="font-size: 0.75rem;">Size: ' . htmlspecialchars($size_name) . '</span>
                            <input type="hidden" name="stock_size_id[]" value="' . htmlspecialchars($item->size_id) . '">
                            <input type="hidden" name="stock_color_id[]" value="' . htmlspecialchars($item->color_id) . '">
                        </div>
                        <div class="mb-2 text-center">
                            <span class="badge bg-info text-dark px-3 py-1" style="font-size: 0.75rem;">Previous: <strong>' . htmlspecialchars($qty) . '</strong></span>
                        </div>
                        <p class="text-center text-muted mb-2" style="font-size: 0.75rem;">Add Quantity</p>
                        <input type="number" step="1" name="size_color_qty[]"
                            class="form-control form-control-sm text-center rounded-pill shadow-sm qty-input"
                            placeholder="0" data-available-qty="' . htmlspecialchars($qty) . '">
                    </div>
                </div>
            </div>';
        }
        $htmlSizeColor .= '</div>';

        $html = '<div class="row g-3">';
        foreach ($stock_sizes as $stock_size) {
            $size_name = session('locale') === 'ar'
                ? $stock_size->size->size_name_ar
                : $stock_size->size->size_name_en;
            $qty = $stock_size->qty;
            $html .= '<div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body text-center p-4">
                        <h5 class="fw-bold text-dark mb-3">' . htmlspecialchars($size_name) . '</h5>
                        <input type="hidden" name="size_id[]" value="' . htmlspecialchars($stock_size->size_id) . '">
                        <input type="number" step="1" name="size_qty[]"
                            class="form-control form-control-lg text-center rounded-pill qty-input"
                            placeholder="0" data-available-qty="' . htmlspecialchars($qty) . '">
                    </div>
                </div>
            </div>';
        }
        $html .= '</div>';

        $htmlColor = '<div class="row g-4">';
        foreach ($stock_colors as $stock_color) {
            $color_name = session('locale') === 'ar'
                ? $stock_color->color->color_name_ar
                : $stock_color->color->color_name_en;
            $color_code = $stock_color->color->color_code ?? '#000';
            $qty = $stock_color->qty ?? 0;
            $htmlColor .= '<div class="col-6 col-md-4 col-lg-3">
                <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                    <div class="card-body text-center p-4">
                        <div class="d-flex flex-column align-items-center gap-2 mb-3">
                            <div class="rounded-circle border border-2" style="width:40px;height:40px;background-color:' . htmlspecialchars($color_code) . '"></div>
                            <h6 class="fw-semibold mb-0">' . htmlspecialchars($color_name) . '</h6>
                            <input type="hidden" name="color_id[]" value="' . htmlspecialchars($stock_color->color_id) . '">
                        </div>
                        <input type="number" step="1" class="form-control form-control-lg text-center rounded-pill qty-input"
                            name="color_qty[]" placeholder="0" data-available-qty="' . htmlspecialchars($qty) . '">
                    </div>
                </div>
            </div>';
        }
        $htmlColor .= '</div>';

        return response()->json([
            'stock_id' => $stock->id,
            'sizes_html' => $html,
            'size_color_html' => $htmlSizeColor,
            'color' => $htmlColor,
            'original_tailors' => [],
            'all_tailors' => [],
        ]);
    }

    public function add_quantity(Request $request)
    {
        $stock_id = $request->stock_id;
        $isPull = $request->qtyType === 'pull';
        $actionType = $isPull ? 2 : 1;

        $user = Auth::guard('tenant')->user();
        $user_id = $user?->id;
        $user_name = $user?->user_name ?? 'System';
        $pull_reason = $isPull ? ($request->pull_reason ?? '') : null;

        if ($request->filled('stock_size_id')) {
            foreach ($request->stock_size_id as $i => $sizeId) {
                $colorId = $request->stock_color_id[$i];
                $item = ColorSize::where('stock_id', $stock_id)
                    ->where('size_id', $sizeId)
                    ->where('color_id', $colorId)
                    ->first();

                if (!$item) continue;

                $old = $item->qty;
                $change = (int) $request->size_color_qty[$i];

                if ($change == 0) continue;

                if ($isPull) {
                    if ($change <= 0) {
                        return response()->json(['status' => 'error', 'message' => 'Pull quantity must be greater than 0'], 400);
                    }
                    if ($change > $old) {
                        return response()->json(['status' => 'error', 'message' => 'Pull quantity cannot exceed available quantity'], 400);
                    }
                    $new = $old - $change;
                } else {
                    $new = $old + $change;
                }

                if ($isPull) {
                    Stock::where('id', $stock_id)->decrement('quantity', $change);
                } else {
                    Stock::where('id', $stock_id)->increment('quantity', $change);
                }
                $item->update(['qty' => $new]);

                StockHistory::create([
                    'stock_id' => $stock_id,
                    'size_id' => $sizeId,
                    'color_id' => $colorId,
                    'old_qty' => $old,
                    'changed_qty' => $change,
                    'new_qty' => $new,
                    'action_type' => $actionType,
                    'tailor_id' => null,
                    'pull_notes' => $pull_reason,
                    'user_id' => $user_id ? (string)$user_id : null,
                    'added_by' => $user_name,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => $isPull ? 'Quantity pulled!' : 'Quantity added!'
        ]);
    }
}
