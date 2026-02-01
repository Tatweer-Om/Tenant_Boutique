<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Tailor;
use App\Models\History;
use App\Models\SpecialOrderItem;
use App\Models\SpecialOrder;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\TailorMaterial;
use App\Models\Material;
use App\Models\MaterialQuantityAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TailorController extends Controller
{
    public function index()
    {
        return view('tailors.tailor');
    }

    public function gettailors()
    {
        return Tailor::orderBy('id', 'DESC')->paginate(10);
    }


    public function store(Request $request)
    {

        $tailor = new Tailor();
        $tailor->tailor_name = $request->tailor_name;
        $tailor->tailor_phone = $request->tailor_phone;
        $tailor->tailor_address = $request->tailor_address;
        $tailor->added_by = 'system';
        $tailor->user_id = 1;
        $tailor->save();

        return response()->json($tailor);
    }

    public function update(Request $request, tailor $tailor)
    {
        $tailor->tailor_name = $request->tailor_name;
        $tailor->tailor_phone = $request->tailor_phone;
        $tailor->tailor_address = $request->tailor_address;
        $tailor->updated_by = 'system_update';
        $tailor->save();

        return response()->json($tailor);
    }


    public function show(tailor $tailor)
    {
        return response()->json($tailor);
    }

    public function destroy(tailor $tailor)
    {
        $tailor->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function tailor_profile($id)
    {
        $tailor = Tailor::findOrFail($id);
        
        // Get real special orders data for this tailor
        $specialOrderItems = SpecialOrderItem::with(['specialOrder.customer'])
            ->where('tailor_id', $id)
            ->orderBy('sent_to_tailor_at', 'DESC')
            ->get();
        
        // Calculate totals (sum of quantities)
        $totalSent = $specialOrderItems->whereNotNull('sent_to_tailor_at')->sum('quantity');
        $totalReceived = $specialOrderItems->where('tailor_status', 'received')->sum('quantity');
        $pending = $specialOrderItems->where('tailor_status', 'processing')->sum('quantity');
        
        // Format items for display
        $items = $specialOrderItems->map(function($item) {
            $order = $item->specialOrder;
            // Get tailor order number (preferred) or special order number (fallback)
            $orderNo = $item->tailor_order_no ?? '—';
            if ($orderNo === '—' && $order) {
                $orderDate = Carbon::parse($order->created_at);
                $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
            }
            
            // Check if this is a stock order
            $isStockOrder = $order && ($order->customer_id === null || $order->source === 'stock');
            
            $sentDate = $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at) : null;
            $daysLate = $sentDate && $item->is_late_delivery ? $sentDate->diffInDays(Carbon::now()) : 0;
            
            return [
                'order_id' => $orderNo,
                'order_number' => 'SO-' . str_pad($order->id ?? 0, 4, '0', STR_PAD_LEFT),
                'customer_name' => $isStockOrder ? trans('messages.stock_special_order', [], session('locale')) : ($order->customer ? $order->customer->name : 'N/A'),
                'abaya_code' => $item->abaya_code ?? '-',
                'design_name' => $item->design_name ?? $item->abaya_code ?? '-',
                'quantity' => $item->quantity ?? 0,
                'status' => $item->tailor_status === 'received' ? 'received' : ($item->tailor_status === 'processing' ? 'processing' : 'new'),
                'sent_date' => $sentDate ? $sentDate->format('Y-m-d') : null,
                'received_date' => $item->received_from_tailor_at ? Carbon::parse($item->received_from_tailor_at)->format('Y-m-d') : null,
                'is_late_delivery' => $item->is_late_delivery ?? false,
                'days_late' => $daysLate,
                'marked_late_at' => $item->marked_late_at ? Carbon::parse($item->marked_late_at)->format('Y-m-d H:i') : null,
            ];
        })->toArray();
        
        // Get late delivery history
        $lateDeliveryItems = SpecialOrderItem::with(['specialOrder.customer'])
            ->where('tailor_id', $id)
            ->where('is_late_delivery', true)
            ->orderBy('marked_late_at', 'DESC')
            ->get()
            ->map(function($item) {
                $order = $item->specialOrder;
                // Get tailor order number (preferred) or special order number (fallback)
                $orderNo = $item->tailor_order_no ?? '—';
                if ($orderNo === '—' && $order) {
                    $orderDate = Carbon::parse($order->created_at);
                    $orderNo = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                }
                
                // Check if this is a stock order
                $isStockOrder = $order && ($order->customer_id === null || $order->source === 'stock');
                
                $sentDate = $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at) : null;
                $daysLate = $sentDate ? $sentDate->diffInDays(Carbon::now()) : 0;
                
                return [
                    'order_no' => $orderNo,
                    'customer_name' => $isStockOrder ? trans('messages.stock_special_order', [], session('locale')) : ($order->customer ? $order->customer->name : 'N/A'),
                    'abaya_code' => $item->abaya_code ?? '-',
                    'design_name' => $item->design_name ?? $item->abaya_code ?? '-',
                    'quantity' => $item->quantity ?? 0,
                    'sent_date' => $sentDate ? $sentDate->format('Y-m-d') : null,
                    'days_late' => $daysLate,
                    'marked_late_at' => $item->marked_late_at ? Carbon::parse($item->marked_late_at)->format('Y-m-d H:i') : null,
                    'current_status' => $item->tailor_status,
                ];
            })->toArray();
        
        $specialOrdersData = [
            'total_sent' => $totalSent,
            'total_received' => $totalReceived,
            'pending' => $pending,
            'items' => $items
        ];

        // Get real stock received data - items received from this tailor
        $receivedItems = SpecialOrderItem::with(['stock.colorSizes.color', 'stock.colorSizes.size', 'stock'])
            ->where('tailor_id', $id)
            ->where('tailor_status', 'received')
            ->whereNotNull('received_from_tailor_at')
            ->orderBy('received_from_tailor_at', 'DESC')
            ->get();
        
        $totalItems = 0;
        $totalValue = 0;
        $items = [];
        $processedStocks = []; // Track processed stock+color+size combinations
        
        foreach ($receivedItems as $item) {
            $stock = $item->stock;
            $receivedDate = $item->received_from_tailor_at ? Carbon::parse($item->received_from_tailor_at)->format('Y-m-d') : null;
            
            if ($stock && $stock->colorSizes->count() > 0) {
                // If stock has color/size combinations, create entries for each
                foreach ($stock->colorSizes as $colorSize) {
                    $colorName = session('locale') === 'ar' 
                        ? ($colorSize->color->color_name_ar ?? '-')
                        : ($colorSize->color->color_name_en ?? '-');
                    
                    $sizeName = session('locale') === 'ar'
                        ? ($colorSize->size->size_name_ar ?? '-')
                        : ($colorSize->size->size_name_en ?? '-');
                    
                    // Create unique key for stock+color+size combination
                    $key = $stock->id . '_' . ($colorSize->color_id ?? '0') . '_' . ($colorSize->size_id ?? '0');
                    
                    if (!isset($processedStocks[$key])) {
                        $qty = $colorSize->qty ?? 0;
                        $totalItems += $qty;
                        
                        // Calculate value (using sales_price from stock)
                        $itemValue = ($stock->sales_price ?? 0) * $qty;
                        $totalValue += $itemValue;
                        
                        $items[] = [
                            'abaya_code' => $stock->abaya_code ?? $item->abaya_code ?? '-',
                            'design_name' => $stock->design_name ?? $item->design_name ?? '-',
                            'color' => $colorName,
                            'size' => $sizeName,
                            'quantity' => $qty,
                            'received_date' => $receivedDate,
                        ];
                        
                        $processedStocks[$key] = true;
                    }
                }
            } else {
                // If no color/size data or no stock, show item without color/size
                $qty = $item->quantity ?? 0;
                $totalItems += $qty;
                
                // Calculate value
                $itemValue = $stock ? (($stock->sales_price ?? 0) * $qty) : 0;
                $totalValue += $itemValue;
                
                $items[] = [
                    'abaya_code' => $stock ? ($stock->abaya_code ?? $item->abaya_code ?? '-') : ($item->abaya_code ?? '-'),
                    'design_name' => $stock ? ($stock->design_name ?? $item->design_name ?? '-') : ($item->design_name ?? '-'),
                    'color' => '-',
                    'size' => '-',
                    'quantity' => $qty,
                    'received_date' => $receivedDate,
                ];
            }
        }
        
        $stockReceivedData = [
            'total_items' => $totalItems,
            'total_value' => $totalValue,
            'items' => $items
        ];

        // Get real materials sent data for this tailor
        $tailorMaterials = TailorMaterial::with('material')
            ->where('tailor_id', $id)
            ->orderBy('sent_date', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->get();
        
        $totalMaterials = $tailorMaterials->count();
        $totalAbayasExpected = $tailorMaterials->sum('abayas_expected');
        
        // Group materials by material_id and sum quantities
        $groupedMaterials = [];
        foreach ($tailorMaterials as $tm) {
            $material = $tm->material;
            $materialId = $material->id ?? 0;
            
            if (!isset($groupedMaterials[$materialId])) {
                $groupedMaterials[$materialId] = [
                    'material_id' => $materialId,
                    'material_name' => $material->material_name ?? '-',
                    'unit' => $material->unit ?? 'pieces',
                    'quantity' => 0,
                ];
            }
            
            $groupedMaterials[$materialId]['quantity'] += floatval($tm->quantity ?? 0);
        }
        
        $items = array_values($groupedMaterials);
        
        $materialsSentData = [
            'total_materials' => $totalMaterials,
            'total_abayas_expected' => $totalAbayasExpected,
            'items' => $items
        ];

        // Get repair history for this tailor
        $repairItems = SpecialOrderItem::with(['specialOrder.customer'])
            ->where('maintenance_tailor_id', $id)
            ->whereIn('maintenance_status', ['delivered_to_tailor', 'received_from_tailor'])
            ->orderBy('sent_for_repair_at', 'desc')
            ->get();
        
        $totalRepairs = $repairItems->count();
        $totalDeliveryCharges = $repairItems->sum('maintenance_delivery_charges');
        $totalRepairCost = $repairItems->sum('maintenance_repair_cost');
        
        $repairHistoryItems = $repairItems->map(function($item) {
            $order = $item->specialOrder;
            $customer = $order ? $order->customer : null;
            
            return [
                'transfer_number' => $item->maintenance_transfer_number ?? '',
                'design_name' => $item->design_name ?? 'N/A',
                'abaya_code' => $item->abaya_code ?? 'N/A',
                'customer_name' => $customer ? $customer->name : 'N/A',
                'customer_phone' => $customer ? $customer->phone : 'N/A',
                'sent_date' => $item->sent_for_repair_at ? Carbon::parse($item->sent_for_repair_at)->format('Y-m-d H:i') : null,
                'received_date' => $item->repaired_at ? Carbon::parse($item->repaired_at)->format('Y-m-d H:i') : null,
                'delivery_charges' => $item->maintenance_delivery_charges ?? 0,
                'repair_cost' => $item->maintenance_repair_cost ?? 0,
                'cost_bearer' => $item->maintenance_cost_bearer ?? null,
                'status' => $item->maintenance_status ?? null,
                'maintenance_notes' => $item->maintenance_notes ?? null,
            ];
        })->toArray();
        
        $repairHistoryData = [
            'total_repairs' => $totalRepairs,
            'total_delivery_charges' => $totalDeliveryCharges,
            'total_repair_cost' => $totalRepairCost,
            'items' => $repairHistoryItems
        ];
        
        $lateDeliveryHistory = [
            'total_late' => count($lateDeliveryItems),
            'items' => $lateDeliveryItems
        ];

        // Get all stocks/abayas for the abaya select box
        $stocks = Stock::select('id', 'abaya_code', 'design_name', 'barcode')
            ->orderBy('abaya_code', 'ASC')
            ->get();

        return view('tailors.tailor_profile', compact('tailor', 'specialOrdersData', 'stockReceivedData', 'materialsSentData', 'repairHistoryData', 'lateDeliveryHistory', 'stocks'));
    }

    public function send_material_to_tailor(Request $request)
    {
        DB::beginTransaction();
        
        try {
            // Validate the request
            $validated = $request->validate([
                'tailor_id' => 'required|exists:tailors,id',
                'material_id' => 'required|exists:materials,id',
                'abaya_id' => 'nullable|exists:stocks,id',
                'quantity' => 'required|numeric|min:0.01',
                'abayas_expected' => 'nullable|integer|min:0',
            ], [
                'tailor_id.required' => 'Tailor ID is required',
                'tailor_id.exists' => 'Selected tailor does not exist',
                'material_id.required' => 'Material ID is required',
                'material_id.exists' => 'Selected material does not exist',
                'abaya_id.exists' => 'Selected abaya does not exist',
                'quantity.required' => 'Quantity is required',
                'quantity.numeric' => 'Quantity must be a number',
                'quantity.min' => 'Quantity must be greater than 0',
                'abayas_expected.integer' => 'Expected abayas must be a whole number',
                'abayas_expected.min' => 'Expected abayas must be 0 or greater',
            ]);

            $user = Auth::user();

            // Get the material to deduct quantity
            $material = Material::findOrFail($validated['material_id']);
            $quantityToDeduct = floatval($validated['quantity']);
            
            // Get tailor for audit log
            $tailor = Tailor::find($validated['tailor_id']);
            $tailorName = $tailor ? $tailor->tailor_name : null;
            
            // Helper function to calculate current quantity
            $getCurrentQuantity = function($material) {
                if ($material->unit === 'roll') {
                    return floatval($material->rolls_count ?? 0);
                } elseif ($material->unit === 'meter' || $material->unit === 'piece') {
                    return floatval($material->meters_per_roll ?? 0);
                } else {
                    return floatval($material->meters_per_roll ?? 0);
                }
            };
            
            // Calculate how many rolls to deduct based on material unit
            $rollsToDeduct = 0;
            if ($material->unit === 'roll') {
                // If unit is roll, deduct directly
                $rollsToDeduct = $quantityToDeduct;
            } elseif ($material->unit === 'meter') {
                // If unit is meter, convert to rolls
                $metersPerRoll = floatval($material->meters_per_roll ?? 1);
                if ($metersPerRoll > 0) {
                    $rollsToDeduct = $quantityToDeduct / $metersPerRoll;
                } else {
                    throw new \Exception('Meters per roll must be greater than zero');
                }
            } elseif ($material->unit === 'piece') {
                // If unit is piece, assume 1 piece = 1 roll (or adjust as needed)
                $rollsToDeduct = $quantityToDeduct;
            } else {
                // Default: treat as rolls
                $rollsToDeduct = $quantityToDeduct;
            }

            // Calculate available quantity based on material unit
            $availableQuantity = 0;
            if ($material->unit === 'roll') {
                $availableQuantity = floatval($material->rolls_count ?? 0);
            } elseif ($material->unit === 'meter') {
                // Available = rolls_count * meters_per_roll (but rolls_count is usually 1, so it's meters_per_roll)
                $availableQuantity = floatval($material->rolls_count ?? 1) * floatval($material->meters_per_roll ?? 0);
            } elseif ($material->unit === 'piece') {
                // For pieces, available = meters_per_roll (which stores total pieces)
                $availableQuantity = floatval($material->meters_per_roll ?? 0);
            } else {
                // Default: use meters_per_roll as total quantity
                $availableQuantity = floatval($material->meters_per_roll ?? 0);
            }
            
            // Check if material has enough quantity - compare directly with available quantity
            if ($availableQuantity < $quantityToDeduct) {
                DB::rollBack();
                $availableText = trans('messages.available', [], session('locale', 'en')) ?: 'Available';
                $requestedText = trans('messages.requested', [], session('locale', 'en')) ?: 'Requested';
                $unitText = $material->unit ?? 'units';
                $insufficientMsg = trans('messages.insufficient_material_quantity', [], session('locale', 'en')) ?: 'Insufficient material quantity';
                
                return response()->json([
                    'status' => 'error',
                    'message' => $insufficientMsg . '. ' . 
                                $availableText . ': ' . number_format($availableQuantity, 2) . ' ' . $unitText . ', ' .
                                $requestedText . ': ' . number_format($quantityToDeduct, 2) . ' ' . $unitText
                ], 422);
            }

            // Check if a TailorMaterial record already exists for this tailor + material combination (abaya_id is optional)
            $query = TailorMaterial::where('tailor_id', $validated['tailor_id'])
                ->where('material_id', $validated['material_id']);
            
            // If abaya_id is provided, include it in the query, otherwise match records without abaya_id
            if (!empty($validated['abaya_id'])) {
                $query->where('abaya_id', $validated['abaya_id']);
            } else {
                $query->whereNull('abaya_id');
            }
            
            $existingTailorMaterial = $query->first();

            if ($existingTailorMaterial) {
                // Update existing record: add quantity and update abayas_expected
                // Note: The existing quantity was already deducted from material, so we only deduct the new quantity
                
                // Check if material has enough for the new quantity being added
                // Recalculate available quantity (it might have changed)
                $availableQuantity = 0;
                if ($material->unit === 'roll') {
                    $availableQuantity = floatval($material->rolls_count ?? 0);
                } elseif ($material->unit === 'meter') {
                    $availableQuantity = floatval($material->rolls_count ?? 1) * floatval($material->meters_per_roll ?? 0);
                } elseif ($material->unit === 'piece') {
                    $availableQuantity = floatval($material->meters_per_roll ?? 0);
                } else {
                    $availableQuantity = floatval($material->meters_per_roll ?? 0);
                }
                
                // Check if available quantity is sufficient
                if ($availableQuantity < $quantityToDeduct) {
                    DB::rollBack();
                    $availableText = trans('messages.available', [], session('locale', 'en')) ?: 'Available';
                    $requestedText = trans('messages.requested', [], session('locale', 'en')) ?: 'Requested';
                    $unitText = $material->unit ?? 'units';
                    $insufficientMsg = trans('messages.insufficient_material_quantity', [], session('locale', 'en')) ?: 'Insufficient material quantity';
                    
                    return response()->json([
                        'status' => 'error',
                        'message' => $insufficientMsg . '. ' . 
                                    $availableText . ': ' . number_format($availableQuantity, 2) . ' ' . $unitText . ', ' .
                                    $requestedText . ': ' . number_format($quantityToDeduct, 2) . ' ' . $unitText
                    ], 422);
                }
                
                // Get previous quantity before deduction
                $previousQuantity = $getCurrentQuantity($material);
                
                // Deduct only the new quantity from material (existing was already deducted previously)
                // Update material quantity based on unit
                if ($material->unit === 'roll') {
                    $currentRolls = floatval($material->rolls_count ?? 0);
                    $material->rolls_count = $currentRolls - $quantityToDeduct;
                } else {
                    // For meter and piece units, deduct from meters_per_roll
                    $currentMetersPerRoll = floatval($material->meters_per_roll ?? 0);
                    $material->meters_per_roll = $currentMetersPerRoll - $quantityToDeduct;
                }
                $material->updated_by = $user ? ($user->user_name ?? $user->name ?? 'system') : 'system';
                if ($user) {
                    $material->user_id = $user->id;
                }
                $material->save();
                
                // Get remaining quantity after deduction
                $remainingQuantity = $getCurrentQuantity($material);
                
                // Get previous tailor material quantity before adding
                $previousTailorMaterialQuantity = floatval($existingTailorMaterial->quantity ?? 0);
                
                // Update existing TailorMaterial record: adjust negative balance first, then add remaining
                $existingQuantity = floatval($existingTailorMaterial->quantity ?? 0);
                $quantityToAdd = floatval($validated['quantity']);
                
                // If existing quantity is negative, first adjust it to zero, then add remaining
                if ($existingQuantity < 0) {
                    $negativeAmount = abs($existingQuantity);
                    if ($quantityToAdd >= $negativeAmount) {
                        // Enough to cover negative balance
                        $existingTailorMaterial->quantity = $quantityToAdd - $negativeAmount;
                    } else {
                        // Not enough to cover negative balance, still negative
                        $existingTailorMaterial->quantity = $existingQuantity + $quantityToAdd;
                    }
                } else {
                    // No negative balance, just add normally
                    $existingTailorMaterial->quantity = $existingQuantity + $quantityToAdd;
                }
                // Update abayas_expected only if provided
                if (isset($validated['abayas_expected']) && $validated['abayas_expected'] > 0) {
                    $existingAbayasExpected = intval($existingTailorMaterial->abayas_expected ?? 0);
                    $existingTailorMaterial->abayas_expected = $validated['abayas_expected'] + $existingAbayasExpected;
                }
                if ($user) {
                    $existingTailorMaterial->user_id = $user->id;
                }
                $existingTailorMaterial->save();
                
                // Get new tailor material quantity after adding
                $newTailorMaterialQuantity = floatval($existingTailorMaterial->quantity ?? 0);
                
                $tailorMaterial = $existingTailorMaterial;
                
                // Log audit entry for material sent to tailor
                try {
                    MaterialQuantityAudit::create([
                        'material_id' => $material->id,
                        'material_name' => $material->material_name,
                        'operation_type' => 'sent_to_tailor',
                        'previous_quantity' => $previousQuantity,
                        'new_quantity' => $remainingQuantity,
                        'quantity_change' => -$quantityToDeduct,
                        'remaining_quantity' => $remainingQuantity,
                        'previous_tailor_material_quantity' => $previousTailorMaterialQuantity,
                        'new_tailor_material_quantity' => $newTailorMaterialQuantity,
                        'tailor_id' => $validated['tailor_id'],
                        'tailor_name' => $tailorName,
                        'user_id' => $user ? $user->id : null,
                        'added_by' => $user ? ($user->user_name ?? $user->name ?? 'system') : 'system',
                        'notes' => 'Material sent to tailor: ' . $tailorName,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error creating material quantity audit: ' . $e->getMessage());
                }
            } else {
                // Get previous quantity before deduction
                $previousQuantity = $getCurrentQuantity($material);
                
                // Deduct quantity from material
                // Update material quantity based on unit
                if ($material->unit === 'roll') {
                    $currentRolls = floatval($material->rolls_count ?? 0);
                    $material->rolls_count = $currentRolls - $quantityToDeduct;
                } else {
                    // For meter and piece units, deduct from meters_per_roll
                    $currentMetersPerRoll = floatval($material->meters_per_roll ?? 0);
                    $material->meters_per_roll = $currentMetersPerRoll - $quantityToDeduct;
                }
                $material->updated_by = $user ? ($user->user_name ?? $user->name ?? 'system') : 'system';
                if ($user) {
                    $material->user_id = $user->id;
                }
                $material->save();

                // Get remaining quantity after deduction
                $remainingQuantity = $getCurrentQuantity($material);

                // Check if there's an existing record with negative balance for this material/tailor/abaya
                $existingNegativeRecord = TailorMaterial::where('tailor_id', $validated['tailor_id'])
                    ->where('material_id', $validated['material_id'])
                    ->where(function($q) use ($validated) {
                        $q->where('abaya_id', $validated['abaya_id'] ?? null)
                          ->orWhereNull('abaya_id');
                    })
                    ->where('quantity', '<', 0)
                    ->first();
                
                $previousTailorMaterialQuantity = 0;
                $quantityToAdd = floatval($validated['quantity']);
                
                if ($existingNegativeRecord) {
                    // Adjust negative balance first
                    $previousTailorMaterialQuantity = floatval($existingNegativeRecord->quantity ?? 0);
                    $negativeAmount = abs($previousTailorMaterialQuantity);
                    
                    if ($quantityToAdd >= $negativeAmount) {
                        // Enough to cover negative balance
                        $existingNegativeRecord->quantity = $quantityToAdd - $negativeAmount;
                        $existingNegativeRecord->save();
                        $tailorMaterial = $existingNegativeRecord;
                        $newTailorMaterialQuantity = floatval($existingNegativeRecord->quantity);
                    } else {
                        // Not enough to cover negative balance, still negative
                        $existingNegativeRecord->quantity = $previousTailorMaterialQuantity + $quantityToAdd;
                        $existingNegativeRecord->save();
                        $tailorMaterial = $existingNegativeRecord;
                        $newTailorMaterialQuantity = floatval($existingNegativeRecord->quantity);
                    }
                } else {
                    // No existing record, create new one
                    $previousTailorMaterialQuantity = 0;
                    $tailorMaterial = TailorMaterial::create([
                        'tailor_id' => $validated['tailor_id'],
                        'material_id' => $validated['material_id'],
                        'abaya_id' => $validated['abaya_id'] ?? null,
                        'quantity' => $validated['quantity'],
                        'abayas_expected' => $validated['abayas_expected'] ?? 0,
                        'status' => 'pending',
                        'sent_date' => now()->format('Y-m-d'),
                        'added_by' => $user ? ($user->user_name ?? $user->name ?? 'system') : 'system',
                        'user_id' => $user ? $user->id : 1,
                    ]);
                    
                    // New tailor material quantity is the quantity just added
                    $newTailorMaterialQuantity = floatval($validated['quantity']);
                }
                
                // Log audit entry for material sent to tailor
                try {
                    MaterialQuantityAudit::create([
                        'material_id' => $material->id,
                        'material_name' => $material->material_name,
                        'operation_type' => 'sent_to_tailor',
                        'previous_quantity' => $previousQuantity,
                        'new_quantity' => $remainingQuantity,
                        'quantity_change' => -$quantityToDeduct,
                        'remaining_quantity' => $remainingQuantity,
                        'previous_tailor_material_quantity' => $previousTailorMaterialQuantity,
                        'new_tailor_material_quantity' => $newTailorMaterialQuantity,
                        'tailor_id' => $validated['tailor_id'],
                        'tailor_name' => $tailorName,
                        'user_id' => $user ? $user->id : null,
                        'added_by' => $user ? ($user->user_name ?? $user->name ?? 'system') : 'system',
                        'notes' => 'Material sent to tailor: ' . $tailorName,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error creating material quantity audit: ' . $e->getMessage());
                }
            }

            // Log the action
            \Log::info('Material sent to tailor', [
                'tailor_id' => $tailorMaterial->tailor_id,
                'material_id' => $tailorMaterial->material_id,
                'quantity' => $tailorMaterial->quantity,
                'abayas_expected' => $tailorMaterial->abayas_expected,
                'user_id' => $user->id ?? 1,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.material_sent_successfully', [], session('locale', 'en')),
                'data' => [
                    'id' => $tailorMaterial->id,
                    'tailor_id' => $tailorMaterial->tailor_id,
                    'material_id' => $tailorMaterial->material_id,
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Validation error sending material to tailor', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.validation_error', [], session('locale', 'en')),
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error sending material to tailor: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.error_saving_material', [], session('locale', 'en')) . ': ' . $e->getMessage()
            ], 500);
        }
    }

    public function materialAudit()
    {
        if (!Auth::check()) {
            return redirect()->route('login_page')->with('error', 'Please login first');
        }

        $tailors = Tailor::orderBy('tailor_name', 'ASC')->get();
        return view('tailors.material_audit', compact('tailors'));
    }

    public function getMaterialAuditData(Request $request)
    {
        try {
            $tailorId = $request->input('tailor_id');
            
            if (!$tailorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tailor ID is required',
                    'data' => []
                ], 400);
            }

            // Get all materials sent to this tailor
            $tailorMaterials = TailorMaterial::with(['material', 'abaya'])
                ->where('tailor_id', $tailorId)
                ->orderBy('sent_date', 'DESC')
                ->orderBy('created_at', 'DESC')
                ->get();

            $auditData = [];

            // Get all received special order items for this tailor (for summary)
            $allReceivedItems = SpecialOrderItem::where('tailor_id', $tailorId)
                ->where('tailor_status', 'received')
                ->whereNotNull('received_from_tailor_at')
                ->get();

            // Get all stock history additions for this tailor (action_type = 1 means addition)
            $stockHistoryAdditions = StockHistory::where('tailor_id', $tailorId)
                ->where('action_type', 1) // 1 = addition
                ->get();

            foreach ($tailorMaterials as $tm) {
                $material = $tm->material;
                $abaya = $tm->abaya;

                // Count received abayas from special orders for this specific abaya
                // Count by stock_id first, then by abaya_code/design_name as fallback
                $specialOrderAbayas = 0; // Count from special orders only
                if ($tm->abaya_id) {
                    // Count items received for this specific stock/abaya
                    $matchingItems = $allReceivedItems->filter(function($item) use ($tm, $abaya) {
                        // Primary match: by stock_id
                        if ($item->stock_id == $tm->abaya_id) {
                            return true;
                        }
                        // Fallback match: by abaya_code or design_name if abaya exists
                        if ($abaya) {
                            return ($item->abaya_code == $abaya->abaya_code) ||
                                   ($item->design_name == $abaya->design_name);
                        }
                        return false;
                    });
                    
                    $specialOrderAbayas = $matchingItems->sum('quantity');
                }
                
                // Count stock additions for this abaya from this tailor
                $stockAdditions = 0;
                if ($tm->abaya_id) {
                    $stockAdditions = $stockHistoryAdditions
                        ->where('stock_id', $tm->abaya_id)
                        ->sum('changed_qty');
                }
                
                // Received Abayas = Special Order Abayas + Stock Additions
                // (Both represent abayas received from the tailor)
                $receivedAbayas = (int)$specialOrderAbayas + (int)$stockAdditions;

                // Count abayas in stock for this tailor
                // Stock Abayas = Current quantity in stock for this abaya that belongs to this tailor
                $stockAbayas = 0;
                if ($tm->abaya_id) {
                    // First, try to get from StockHistory (most accurate)
                    $stockAbayasFromHistory = $stockHistoryAdditions
                        ->where('stock_id', $tm->abaya_id)
                        ->sum('changed_qty');
                    
                    // Also check current stock quantity
                    $stock = Stock::with('colorSizes')->find($tm->abaya_id);
                    if ($stock) {
                        $hasTailor = false;
                        if ($stock->tailor_id) {
                            $tailorIds = json_decode($stock->tailor_id, true);
                            if ($tailorIds !== null) {
                                if (!is_array($tailorIds)) {
                                    $tailorIds = [$tailorIds];
                                }
                                
                                foreach ($tailorIds as $id) {
                                    $normalizedId = is_numeric($id) ? (int)$id : (int)trim($id);
                                    $normalizedTailorId = (int)$tailorId;
                                    if ($normalizedId == $normalizedTailorId) {
                                        $hasTailor = true;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        if ($hasTailor) {
                            // Calculate quantity based on stock mode
                            if ($stock->mode === 'color_size') {
                                if (!$stock->relationLoaded('colorSizes')) {
                                    $stock->load('colorSizes');
                                }
                                if ($stock->colorSizes && $stock->colorSizes->count() > 0) {
                                    $stockAbayas = (int)$stock->colorSizes->sum('qty');
                                } else {
                                    $stockAbayas = (int)($stock->quantity ?? 0);
                                }
                            } else {
                                $stockAbayas = (int)($stock->quantity ?? 0);
                            }
                        }
                    }
                    
                    // Use StockHistory if it's higher (more accurate tracking)
                    if ($stockAbayasFromHistory > $stockAbayas) {
                        $stockAbayas = (int)$stockAbayasFromHistory;
                    }
                }

                // Count special orders received for this abaya (count of orders, not quantity)
                $specialOrdersReceived = 0;
                if ($tm->abaya_id) {
                    $specialOrdersReceived = $allReceivedItems
                        ->filter(function($item) use ($tm, $abaya) {
                            // Primary match: by stock_id
                            if ($item->stock_id == $tm->abaya_id) {
                                return true;
                            }
                            // Fallback match: by abaya_code or design_name if abaya exists
                            if ($abaya) {
                                return ($item->abaya_code == $abaya->abaya_code) ||
                                       ($item->design_name == $abaya->design_name);
                            }
                            return false;
                        })
                        ->count(); // Count distinct orders
                }

                // Generate transaction ID
                $transactionId = 'TMAT-' . str_pad($tm->id, 6, '0', STR_PAD_LEFT);

                // Calculate total meters/pieces based on material data
                $totalMetersOrPieces = '-';
                $totalMetersOrPiecesValue = 0;
                $tmQuantity = floatval($tm->quantity ?? 0);
                $materialMetersPerRoll = floatval($material->meters_per_roll ?? 0);
                
                if ($material->unit === 'roll' && $materialMetersPerRoll > 0) {
                    // Calculate total meters: stored quantity (in rolls) × meters_per_roll
                    $totalMetersOrPiecesValue = $tmQuantity * $materialMetersPerRoll;
                    $totalMetersOrPieces = number_format($totalMetersOrPiecesValue, 2) . ' ' . trans('messages.meter', [], session('locale', 'en'), 'meters');
                } elseif ($material->unit === 'meter') {
                    // Quantity is already in meters
                    $totalMetersOrPiecesValue = $tmQuantity;
                    $totalMetersOrPieces = number_format($totalMetersOrPiecesValue, 2) . ' ' . trans('messages.meter', [], session('locale', 'en'), 'meters');
                } elseif ($material->unit === 'piece') {
                    // Quantity is in pieces
                    $totalMetersOrPiecesValue = $tmQuantity;
                    $totalMetersOrPieces = number_format($totalMetersOrPiecesValue, 2) . ' ' . trans('messages.piece', [], session('locale', 'en'), 'pieces');
                }

                $auditData[] = [
                    'transaction_id' => $transactionId,
                    'material_id' => $tm->id,
                    'material_name' => $material->material_name ?? 'N/A',
                    'material_code' => 'MAT-' . str_pad($material->id ?? 0, 4, '0', STR_PAD_LEFT),
                    'abaya_id' => $tm->abaya_id,
                    'abaya_name' => $abaya ? ($abaya->design_name ?? $abaya->abaya_code ?? 'N/A') : 'N/A',
                    'abaya_code' => $abaya ? ($abaya->abaya_code ?? 'N/A') : 'N/A',
                    'barcode' => $abaya ? ($abaya->barcode ?? '-') : '-',
                    'sent_date' => $tm->sent_date ? Carbon::parse($tm->sent_date)->format('Y-m-d') : 'N/A',
                    'quantity' => number_format($tmQuantity, 2),
                    'total_meters_pieces' => $totalMetersOrPieces,
                    'total_meters_pieces_value' => $totalMetersOrPiecesValue,
                    'abayas_expected' => $tm->abayas_expected ?? 0,
                    'received_abayas' => (int)$receivedAbayas,
                    'stock_abayas' => (int)$stockAbayas,
                    'special_order_abayas' => (int)$specialOrderAbayas,
                    'special_orders_received' => $specialOrdersReceived,
                    'status' => $tm->status ?? 'pending',
                    'category' => $material->category ?? '-',
                ];
            }

            // Group audit data by abaya_id for summary display
            $groupedByAbaya = [];
            foreach ($auditData as $item) {
                $abayaId = $item['abaya_id'] ?? null;
                if (!isset($groupedByAbaya[$abayaId])) {
                    $groupedByAbaya[$abayaId] = [
                        'abaya_code' => $item['abaya_code'],
                        'abaya_name' => $item['abaya_name'],
                        'materials' => []
                    ];
                }
                $groupedByAbaya[$abayaId]['materials'][] = [
                    'material_name' => $item['material_name'],
                    'abayas_expected' => $item['abayas_expected']
                ];
            }

            // Get summary totals
            $totalMaterials = count($auditData);
            $totalReceived = array_sum(array_column($auditData, 'received_abayas'));
            $totalStock = array_sum(array_column($auditData, 'stock_abayas'));
            $totalSpecialOrderAbayas = array_sum(array_column($auditData, 'special_order_abayas'));
            $totalSpecialOrders = $allReceivedItems->count();
            
            // Calculate total stock abayas from StockHistory (all additions for this tailor)
            $totalStockFromHistory = $stockHistoryAdditions->sum('changed_qty');

            return response()->json([
                'success' => true,
                'data' => $auditData,
                'summary' => [
                    'total_materials' => $totalMaterials,
                    'total_expected_abayas' => 0, // Will be calculated on frontend grouped by abaya
                    'grouped_by_abaya' => array_values($groupedByAbaya), // Grouped data for display
                    'total_received_abayas' => $totalReceived,
                    'total_stock_abayas' => $totalStock,
                    'total_special_order_abayas' => $totalSpecialOrderAbayas,
                    'total_stock_from_history' => $totalStockFromHistory,
                    'total_special_orders' => $totalSpecialOrders,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getMaterialAuditData: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching audit data: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}
