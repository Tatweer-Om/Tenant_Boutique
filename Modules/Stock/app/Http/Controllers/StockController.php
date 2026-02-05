<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\StockController as BaseStockController;
use App\Models\Stock;
use App\Models\Color;
use App\Models\Size;
use App\Models\Category;
use App\Models\ColorSize;
use App\Models\StockSize;
use App\Models\StockColor;
use App\Models\StockImage;
use App\Models\StockHistory;
use App\Models\StockAuditLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StockController extends BaseStockController
{
    /**
     * Show stock list view (Stock module - tenant auth)
     */
    public function view_stock()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        return view('stock::view_stock', compact('permissions'));
    }

    /**
     * Add stock form (Stock module - no tailor/material)
     */
    public function add_stock_form()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];
        if (!in_array(9, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        $colors = Color::all();
        $sizes = Size::all();
        $categories = Category::all();

        return view('stock::add_stock', compact('colors', 'sizes', 'categories'));
    }

    /**
     * Edit stock (Stock module - no tailor/material)
     */
    public function edit_stock($id)
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];
        if (!in_array(9, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        $colors = Color::all();
        $sizes = Size::all();
        $categories = Category::all();

        $stock = Stock::with([
            'colors',
            'sizes',
            'colorSizes',
            'images',
            'category',
        ])->findOrFail($id);

        $returnPage = (int) request()->get('page', 1);
        if ($returnPage < 1) {
            $returnPage = 1;
        }

        return view('stock::edit_stock', compact('colors', 'sizes', 'categories', 'id', 'stock', 'returnPage'));
    }

    /**
     * Get stock quantity form HTML for manage quantities popup (Stock module - no tailor)
     */
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
                <div class="rounded-circle border border-2 shadow-sm" style="width:18px;height:18px;background-color:' . htmlspecialchars($color_code) . ';"></div>
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
            <input type="number" step="1" name="size_color_qty[]" class="form-control form-control-sm text-center rounded-pill shadow-sm qty-input" placeholder="0" data-available-qty="' . htmlspecialchars($qty) . '">
        </div>
    </div>
</div>';
        }
        $htmlSizeColor .= '</div>';

        $htmlColor = '<div class="row g-4">';
        foreach ($stock_colors as $stock_color) {
            $color_name = session('locale') === 'ar'
                ? $stock_color->color->color_name_ar
                : $stock_color->color->color_name_en;
            $color_code = $stock_color->color->color_code ?? '#000';
            $qty = $stock_color->qty ?? 0;
            $htmlColor .= '
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100 border-0 shadow-sm hover-shadow transition">
                <div class="card-body text-center p-4">
                    <div class="d-flex flex-column align-items-center gap-2 mb-3">
                        <div class="rounded-circle border border-2" style="width:40px;height:40px;background-color:' . htmlspecialchars($color_code) . '"></div>
                        <h6 class="fw-semibold mb-0">' . htmlspecialchars($color_name) . '</h6>
                        <input type="hidden" name="color_id[]" value="' . htmlspecialchars($stock_color->color_id) . '">
                    </div>
                    <input type="number" step="1" class="form-control form-control-lg text-center rounded-pill qty-input" name="color_qty[]" placeholder="0" data-available-qty="' . htmlspecialchars($qty) . '">
                </div>
            </div>
        </div>';
        }
        $htmlColor .= '</div>';

        $html = '<div class="row g-3">';
        foreach ($stock_sizes as $stock_size) {
            $size_name = session('locale') === 'ar'
                ? $stock_size->size->size_name_ar
                : $stock_size->size->size_name_en;
            $qty = $stock_size->qty;
            $html .= '
    <div class="col-6 col-md-4 col-lg-3">
        <div class="card h-100 border-0 shadow-sm hover-shadow transition">
            <div class="card-body text-center p-4">
                <h5 class="fw-bold text-dark mb-3">' . htmlspecialchars($size_name) . '</h5>
                <input type="hidden" name="size_id[]" value="' . htmlspecialchars($stock_size->size_id) . '">
                <input type="number" step="1" name="size_qty[]" class="form-control form-control-lg text-center rounded-pill qty-input" placeholder="0" data-available-qty="' . htmlspecialchars($qty) . '">
            </div>
        </div>
    </div>';
        }
        $html .= '</div>';

        return response()->json([
            'stock_id' => $stock->id,
            'sizes_html' => $html,
            'size_color_html' => $htmlSizeColor,
            'color' => $htmlColor,
            'original_tailors' => [],
            'all_tailors' => [],
        ]);
    }

    /**
     * Show comprehensive stock audit log page (Stock module - no special order)
     */
    public function comprehensiveAudit()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(9, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        return view('stock::comprehensive_audit');
    }

    /**
     * Get comprehensive audit logs (Stock module - excludes special_order)
     */
    public function getComprehensiveAudit(Request $request)
    {
        try {
            if (!Schema::hasTable('stock_audit_logs')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'remaining_quantity' => null,
                    'remaining_by_size' => null,
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => 10,
                ]);
            }

            $search = $request->input('search', '');
            $fromDate = $request->input('from_date');
            $toDate = $request->input('to_date');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            $query = StockAuditLog::with(['stock', 'user', 'size'])
                ->where(function ($q) {
                    $q->where('operation_type', '!=', 'special_order')
                        ->orWhereNull('operation_type');
                });

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('abaya_code', 'like', '%' . $search . '%')
                        ->orWhere('barcode', 'like', '%' . $search . '%')
                        ->orWhere('design_name', 'like', '%' . $search . '%');
                });
            }
            if (!empty($fromDate)) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
            if (!empty($toDate)) {
                $query->whereDate('created_at', '<=', $toDate);
            }

            $allLogs = $query->orderBy('created_at', 'DESC')->get();
            $grouped = [];
            foreach ($allLogs as $log) {
                $dateTime = \Carbon\Carbon::parse($log->created_at)->format('Y-m-d H:i');
                $groupKey = ($log->barcode ?? $log->abaya_code ?? '') . '|' .
                    ($log->size_id ?? '') . '|' .
                    ($log->operation_type ?? '') . '|' .
                    ($log->related_id ?? '') . '|' .
                    $dateTime;

                if (!isset($grouped[$groupKey])) {
                    $grouped[$groupKey] = [
                        'logs' => [],
                        'total_quantity_change' => 0,
                        'min_previous_quantity' => PHP_INT_MAX,
                        'first_log' => $log,
                    ];
                }
                $grouped[$groupKey]['logs'][] = $log;
                $grouped[$groupKey]['total_quantity_change'] += $log->quantity_change;
                if ($log->previous_quantity < $grouped[$groupKey]['min_previous_quantity']) {
                    $grouped[$groupKey]['min_previous_quantity'] = $log->previous_quantity;
                }
            }

            foreach ($grouped as $key => $group) {
                $grouped[$key]['new_quantity'] = ($group['min_previous_quantity'] === PHP_INT_MAX ? 0 : $group['min_previous_quantity']) + $group['total_quantity_change'];
            }

            $groupedArray = array_values($grouped);
            usort($groupedArray, function ($a, $b) {
                return strcmp($b['first_log']->created_at, $a['first_log']->created_at);
            });

            $total = count($groupedArray);
            $offset = ($page - 1) * $perPage;
            $paginated = array_slice($groupedArray, $offset, $perPage);

            $locale = session('locale', 'en');
            $operationTypeLabels = [
                'added' => trans('messages.stock_addition', [], $locale) ?: 'Added',
                'updated' => trans('messages.quantity_added', [], $locale) ?: 'Updated',
                'sold' => trans('messages.quantity_sold_pos', [], $locale) ?: 'Sold',
                'transferred' => trans('messages.quantity_transferred_out', [], $locale) ?: 'Transferred',
            ];

            $customerIdsForSold = [];
            foreach ($paginated as $group) {
                $log = $group['first_log'];
                if ($log->operation_type === 'sold' && !empty($log->related_info['customer_id'])) {
                    $customerIdsForSold[] = $log->related_info['customer_id'];
                }
            }
            $customers = [];
            if (!empty($customerIdsForSold)) {
                $customers = Customer::whereIn('id', array_unique($customerIdsForSold))
                    ->get(['id', 'name', 'phone'])
                    ->keyBy('id')
                    ->toArray();
            }

            $formattedData = array_map(function ($group) use ($operationTypeLabels, $locale, $customers) {
                $log = $group['first_log'];
                $previousQty = $group['min_previous_quantity'] === PHP_INT_MAX ? 0 : $group['min_previous_quantity'];
                $relatedInfo = $log->related_info;
                $relatedDetails = '';
                if ($log->operation_type === 'transferred' && $relatedInfo) {
                    $relatedDetails = ($relatedInfo['from'] ?? '') . ' → ' . ($relatedInfo['to'] ?? '');
                } elseif ($log->operation_type === 'sold' && $relatedInfo) {
                    $customerId = $relatedInfo['customer_id'] ?? null;
                    if ($customerId && isset($customers[$customerId])) {
                        $c = $customers[$customerId];
                        $relatedDetails = ($c['name'] ?? 'N/A') . ' (' . ($c['phone'] ?? 'N/A') . ')';
                    } else {
                        $relatedDetails = 'Customer ID: ' . ($customerId ?? 'N/A');
                    }
                }

                $sizeName = '—';
                if (!empty($log->size)) {
                    $sizeName = $locale === 'ar'
                        ? ($log->size->size_name_ar ?? $log->size->size_name_en ?? '—')
                        : ($log->size->size_name_en ?? $log->size->size_name_ar ?? '—');
                }

                $addedBy = $log->added_by ?? 'System';
                if (count($group['logs']) > 1) {
                    $users = array_unique(array_filter(array_map(fn($l) => $l->added_by ?? null, $group['logs'])));
                    if (count($users) > 1) {
                        $addedBy = implode(', ', $users);
                    }
                }

                return [
                    'id' => $log->id,
                    'abaya_code' => $log->abaya_code ?? '—',
                    'barcode' => $log->barcode ?? '—',
                    'size' => $sizeName,
                    'design_name' => $log->design_name ?? '—',
                    'operation_type' => $log->operation_type,
                    'operation_label' => $operationTypeLabels[$log->operation_type] ?? $log->operation_type,
                    'previous_quantity' => $previousQty,
                    'new_quantity' => $group['new_quantity'],
                    'quantity_change' => $group['total_quantity_change'],
                    'related_id' => $log->related_id ?? '—',
                    'related_details' => $relatedDetails,
                    'added_by' => $addedBy,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'date' => $log->created_at->format('Y-m-d'),
                    'time' => $log->created_at->format('H:i:s'),
                ];
            }, $paginated);

            $remainingQty = null;
            $remainingBySize = null;
            if (!empty($search)) {
                $barcodeLogs = StockAuditLog::where(function ($q) {
                    $q->where('operation_type', '!=', 'special_order')->orWhereNull('operation_type');
                })->where(function ($q) use ($search) {
                    $q->where('abaya_code', 'like', '%' . $search . '%')
                        ->orWhere('barcode', 'like', '%' . $search . '%')
                        ->orWhere('design_name', 'like', '%' . $search . '%');
                })->with('size')->orderBy('created_at', 'ASC')->get();

                if ($barcodeLogs->count() > 0) {
                    $finalBySize = [];
                    foreach ($barcodeLogs as $l) {
                        $sid = $l->size_id ?? 0;
                        $finalBySize[$sid] = (int)($l->new_quantity ?? 0);
                    }
                    $remainingQty = array_sum($finalBySize);
                    $remainingBySize = [];
                    foreach ($finalBySize as $sid => $qty) {
                        $sizeName = '—';
                        if (!empty($sid)) {
                            $size = $barcodeLogs->firstWhere('size_id', $sid)?->size;
                            if ($size) {
                                $sizeName = $locale === 'ar'
                                    ? ($size->size_name_ar ?? $size->size_name_en ?? '—')
                                    : ($size->size_name_en ?? $size->size_name_ar ?? '—');
                            }
                        }
                        $remainingBySize[] = ['size_id' => (int)$sid, 'size' => $sizeName, 'quantity' => (int)$qty];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $formattedData,
                'remaining_quantity' => $remainingQty,
                'remaining_by_size' => $remainingBySize,
                'current_page' => (int)$page,
                'last_page' => (int)ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching audit data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock list (Stock module - no tailor names)
     */
    public function getstock()
    {
        $stocks = Stock::with([
            'colorSizes.size',
            'colorSizes.color',
            'images',
            'category',
        ])
            ->orderBy('id', 'DESC')
            ->paginate(10);

        foreach ($stocks->items() as $stock) {
            $stock->tailor_names = [];
            $stock->tailor_names_display = '-';
        }

        return response()->json($stocks);
    }

    /**
     * Update stock (Stock module - no tailor/material)
     */
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
        $stock->website_data_delivery_status = 1;

        $totalQty = 0;
        if (!empty($request->colors)) {
            foreach ($request->colors as $c) {
                $totalQty += (float) ($c['qty'] ?? 0);
            }
        }
        if (!empty($request->sizes)) {
            foreach ($request->sizes as $s) {
                $totalQty += (float) ($s['qty'] ?? 0);
            }
        }
        if (!empty($request->color_sizes)) {
            foreach ($request->color_sizes as $color_id => $sizes) {
                if (is_array($sizes)) {
                    foreach ($sizes as $size_id => $data) {
                        $totalQty += (float) ($data['qty'] ?? 0);
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
                $stock_img->image_path = asset('images/stock_images/' . $imageName);
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

    /**
     * Add stock (Stock module - no tailor/material)
     */
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
        $hasColorOnly = false;
        if ($request->filled('colors') && is_array($request->colors)) {
            foreach ($request->colors as $c) {
                if (isset($c['qty']) && floatval($c['qty'] ?? 0) > 0) {
                    $hasColorOnly = true;
                    break;
                }
            }
        }
        $hasSizeOnly = false;
        if ($request->filled('sizes') && is_array($request->sizes)) {
            foreach ($request->sizes as $s) {
                if (isset($s['qty']) && floatval($s['qty'] ?? 0) > 0) {
                    $hasSizeOnly = true;
                    break;
                }
            }
        }
        if (!$hasColorSize && !$hasColorOnly && !$hasSizeOnly) {
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.enter_color_size', [], session('locale')) ?: 'Please add at least one color and size combination'
            ], 422);
        }

        $totalQty = 0;
        if (!empty($request->color_sizes)) {
            foreach ($request->color_sizes as $color_id => $sizes) {
                foreach ($sizes as $size_id => $data) {
                    $totalQty += $data['qty'] ?? 0;
                }
            }
        }
        if (!empty($request->colors)) {
            foreach ($request->colors as $c) {
                $totalQty += $c['qty'] ?? 0;
            }
        }
        if (!empty($request->sizes)) {
            foreach ($request->sizes as $s) {
                $totalQty += $s['qty'] ?? 0;
            }
        }

        $stock = new Stock();
        $stock->abaya_code = $request->abaya_code;
        $stock->design_name = $request->design_name;
        $stock->barcode = $request->barcode;
        $stock->abaya_notes = $request->abaya_notes;
        $stock->category_id = $request->category_id;
        $stock->cost_price = $request->cost_price;
        $stock->sales_price = $request->sales_price;
        $stock->quantity = $totalQty;
        $stock->notification_limit = $request->notification_limit;
        $stock->mode = $request->mode;
        $stock->save();

        $user_id = Auth::guard('tenant')->id();
        $user = \App\Models\User::find($user_id);
        $user_name = $user ? $user->user_name : 'System';

        if (!empty($request->colors)) {
            foreach ($request->colors as $color) {
                $qty = $color['qty'] ?? 0;
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
                        'user_id' => $user_id ? (string) $user_id : null,
                        'added_by' => $user_name,
                    ]);
                    $this->logStockAudit($stock, 'added', 0, $qty, null, null, null, $color['color_id'], null, 'Initial stock addition - Color only');
                }
            }
        }

        if (!empty($request->sizes)) {
            foreach ($request->sizes as $size_id => $size) {
                $qty = $size['qty'] ?? 0;
                $stockSize = new StockSize();
                $stockSize->stock_id = $stock->id;
                $stockSize->size_id = $size_id;
                $stockSize->qty = $qty;
                $stockSize->save();
                if ($qty > 0) {
                    StockHistory::create([
                        'stock_id' => $stock->id,
                        'size_id' => $size_id,
                        'color_id' => null,
                        'old_qty' => 0,
                        'changed_qty' => $qty,
                        'new_qty' => $qty,
                        'action_type' => 1,
                        'tailor_id' => null,
                        'pull_notes' => null,
                        'user_id' => $user_id ? (string) $user_id : null,
                        'added_by' => $user_name,
                    ]);
                    $this->logStockAudit($stock, 'added', 0, $qty, null, null, null, null, $size_id, 'Initial stock addition - Size only');
                }
            }
        }

        if (!empty($request->color_sizes)) {
            foreach ($request->color_sizes as $color_id => $sizes) {
                foreach ($sizes as $size_id => $data) {
                    $qty = $data['qty'] ?? 0;
                    $colorSize = new ColorSize();
                    $colorSize->stock_id = $stock->id;
                    $colorSize->color_id = $color_id;
                    $colorSize->size_id = $data['size_id'];
                    $colorSize->qty = $qty;
                    $colorSize->save();
                    if ($qty > 0) {
                        StockHistory::create([
                            'stock_id' => $stock->id,
                            'size_id' => $data['size_id'],
                            'color_id' => $color_id,
                            'old_qty' => 0,
                            'changed_qty' => $qty,
                            'new_qty' => $qty,
                            'action_type' => 1,
                            'tailor_id' => null,
                            'pull_notes' => null,
                            'user_id' => $user_id ? (string) $user_id : null,
                            'added_by' => $user_name,
                        ]);
                        $this->logStockAudit($stock, 'added', 0, $qty, null, null, null, $color_id, $data['size_id'], 'Initial stock addition - Color & Size');
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
                $stock_img->image_path = asset('images/stock_images/' . $imageName);
                $stock_img->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Stock added successfully!',
            'redirect_url' => url('view_stock'),
        ]);
    }
}
