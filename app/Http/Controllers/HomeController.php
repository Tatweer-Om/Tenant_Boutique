<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\SpecialOrder;
use App\Models\PosOrders;
use App\Models\Settlement;
use App\Models\SpecialOrderItem;
use App\Models\Tailor;
use App\Models\Stock;
use App\Models\ColorSize;
use App\Models\StockColor;
use App\Models\StockSize;
use App\Models\Boutique;
use App\Models\BoutiqueInvo;

class HomeController extends Controller
{
    public function index(){
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Calculate Revenue for Today
        $todayRevenue = $this->calculateTodayRevenue($today);
        
        // Calculate Expenses for Today (delivery charges if paid)
        $todayExpenses = $this->calculateTodayExpenses($today);
        
        // Calculate Net Profit for Today
        $todayNetProfit = $todayRevenue - $todayExpenses;
        
        // Calculate Revenue for This Month
        $monthRevenue = $this->calculateMonthRevenue($startOfMonth, $endOfMonth);
        
        // Calculate Expenses for This Month
        $monthExpenses = $this->calculateMonthExpenses($startOfMonth, $endOfMonth);
        
        // Calculate Net Profit for This Month
        $monthNetProfit = $monthRevenue - $monthExpenses;
        
        // Calculate Revenue - Expense (overall)
        $totalRevenue = $monthRevenue;
        $totalExpenses = $monthExpenses;
        $revenueMinusExpense = $totalRevenue - $totalExpenses;
        
        // Count orders with tailor (items with tailor_status = 'processing')
        $ordersWithTailor = SpecialOrderItem::where('tailor_status', 'processing')->count();
        
        // Calculate monthly revenue and expenses for the current year
        $currentYear = Carbon::now()->year;
        $monthlyData = $this->calculateMonthlyData($currentYear);
        
        return view('dashboard.dashboard', compact(
            'todayNetProfit',
            'monthNetProfit',
            'revenueMinusExpense',
            'ordersWithTailor',
            'todayRevenue',
            'todayExpenses',
            'totalRevenue',
            'totalExpenses',
            'monthlyData',
            'currentYear'
        ));
    }
    
    private function calculateTodayRevenue($date)
    {
        // Special Orders revenue (delivered orders only)
        $specialOrdersRevenue = SpecialOrder::where('status', 'delivered')
            ->whereDate('updated_at', $date)
            ->sum('total_amount');
        
        // POS Orders revenue (exclude returns)
        $posRevenue = PosOrders::where(function($q) {
                $q->where('return_status', '!=', 'returned')
                  ->orWhereNull('return_status');
            })
            ->whereDate('created_at', $date)
            ->sum('total_amount');
        
        // Settlements revenue (for today's date range)
        $settlementsRevenue = Settlement::whereDate('created_at', $date)
            ->sum('total_sales');
        
        return floatval($specialOrdersRevenue) + floatval($posRevenue) + floatval($settlementsRevenue);
    }
    
    private function calculateTodayExpenses($date)
    {
        // POS Orders delivery charges (if delivery_paid is true or 1)
        $posDeliveryCharges = PosOrders::where(function($q) {
                $q->where('return_status', '!=', 'returned')
                  ->orWhereNull('return_status');
            })
            ->whereDate('created_at', $date)
            ->where(function($q) {
                $q->where('delivery_paid', 1)
                  ->orWhere('delivery_paid', true);
            })
            ->sum('delivery_charges');
        
        // Special Orders shipping fees (if order is delivered, consider shipping fee as expense)
        $specialOrdersShipping = SpecialOrder::where('status', 'delivered')
            ->whereDate('updated_at', $date)
            ->sum('shipping_fee');
        
        return floatval($posDeliveryCharges) + floatval($specialOrdersShipping);
    }
    
    private function calculateMonthRevenue($startDate, $endDate)
    {
        // Special Orders revenue (delivered orders only)
        $specialOrdersRevenue = SpecialOrder::where('status', 'delivered')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('total_amount');
        
        // POS Orders revenue (exclude returns)
        $posRevenue = PosOrders::where(function($q) {
                $q->where('return_status', '!=', 'returned')
                  ->orWhereNull('return_status');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');
        
        // Settlements revenue
        $settlementsRevenue = Settlement::whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_sales');
        
        return floatval($specialOrdersRevenue) + floatval($posRevenue) + floatval($settlementsRevenue);
    }
    
    private function calculateMonthExpenses($startDate, $endDate)
    {
        // POS Orders delivery charges (if delivery_paid is true or 1)
        $posDeliveryCharges = PosOrders::where(function($q) {
                $q->where('return_status', '!=', 'returned')
                  ->orWhereNull('return_status');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function($q) {
                $q->where('delivery_paid', 1)
                  ->orWhere('delivery_paid', true);
            })
            ->sum('delivery_charges');
        
        // Special Orders shipping fees (if order is delivered)
        $specialOrdersShipping = SpecialOrder::where('status', 'delivered')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('shipping_fee');
        
        return floatval($posDeliveryCharges) + floatval($specialOrdersShipping);
    }
    
    private function calculateMonthlyData($year)
    {
        $monthlyRevenue = [];
        $monthlyExpenses = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
            
            // Calculate revenue for this month
            $revenue = $this->calculateMonthRevenue($startOfMonth, $endOfMonth);
            $monthlyRevenue[] = $revenue;
            
            // Calculate expenses for this month
            $expenses = $this->calculateMonthExpenses($startOfMonth, $endOfMonth);
            $monthlyExpenses[] = $expenses;
        }
        
        return [
            'revenue' => $monthlyRevenue,
            'expenses' => $monthlyExpenses
        ];
    }
    
    public function getMonthlyData(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $monthlyData = $this->calculateMonthlyData($year);
        
        return response()->json([
            'success' => true,
            'revenue' => $monthlyData['revenue'],
            'expenses' => $monthlyData['expenses']
        ]);
    }
    
    public function getAbayasUnderTailoring()
    {
        try {
            $items = SpecialOrderItem::with(['specialOrder.customer', 'tailor', 'stock.images'])
                ->where('tailor_status', 'processing')
                ->whereNotNull('sent_to_tailor_at')
                ->orderBy('sent_to_tailor_at', 'DESC')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    $order = $item->specialOrder;
                    $tailor = $item->tailor;
                    $stock = $item->stock;
                    
                    $orderDate = $order ? Carbon::parse($order->created_at) : null;
                    $orderNo = $orderDate ? $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT) : '—';
                    
                    $sentDate = $item->sent_to_tailor_at ? Carbon::parse($item->sent_to_tailor_at) : null;
                    $daysSinceSent = $sentDate ? $sentDate->diffInDays(Carbon::now()) : 0;
                    
                    return [
                        'id' => $item->id,
                        'order_no' => $orderNo,
                        'order_id' => $order->id ?? 0,
                        'customer_name' => $order->customer->name ?? 'N/A',
                        'abaya_code' => $item->abaya_code ?? 'N/A',
                        'design_name' => $item->design_name ?? $item->abaya_code ?? 'N/A',
                        'tailor_name' => $tailor->tailor_name ?? 'N/A',
                        'tailor_id' => $item->tailor_id,
                        'sent_date' => $sentDate ? $sentDate->format('Y-m-d') : null,
                        'sent_date_formatted' => $sentDate ? $sentDate->format('d/m/Y') : null,
                        'days_since_sent' => $daysSinceSent,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'items' => $items
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching abayas under tailoring: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'items' => []
            ], 500);
        }
    }
    
    public function getLowStockItems()
    {
        try {
            $lowStockItems = [];
            $lowStockThreshold = 3; // Show items with quantity <= 3
            
            // Get all stocks
            $stocks = Stock::with(['colorSizes', 'colors', 'sizes'])
                ->get();
            
            foreach ($stocks as $stock) {
                $totalQty = 0;
                
                // Calculate total quantity based on mode
                if ($stock->mode === 'color_size') {
                    // Sum from color_sizes table
                    $totalQty = ColorSize::where('stock_id', $stock->id)->sum('qty');
                } elseif ($stock->mode === 'color') {
                    // Sum from stock_colors table
                    $totalQty = StockColor::where('stock_id', $stock->id)->sum('qty');
                } elseif ($stock->mode === 'size') {
                    // Sum from stock_sizes table
                    $totalQty = StockSize::where('stock_id', $stock->id)->sum('qty');
                } else {
                    // Fallback to stock quantity field
                    $totalQty = (int)($stock->quantity ?? 0);
                }
                
                // Check if quantity is less than or equal to 3
                if ($totalQty <= $lowStockThreshold) {
                    // Calculate percentage for progress bar (using threshold of 3 as 100%)
                    $percentage = $lowStockThreshold > 0 
                        ? ($totalQty / $lowStockThreshold) * 100 
                        : 0;
                    
                    $lowStockItems[] = [
                        'id' => $stock->id,
                        'abaya_code' => $stock->abaya_code ?? 'N/A',
                        'design_name' => $stock->design_name ?? $stock->abaya_code ?? 'N/A',
                        'remaining' => $totalQty,
                        'threshold' => $lowStockThreshold,
                        'percentage' => min($percentage, 100),
                    ];
                }
            }
            
            // Sort by remaining quantity (lowest first)
            usort($lowStockItems, function($a, $b) {
                return $a['remaining'] <=> $b['remaining'];
            });
            
            // Limit to top 10
            $lowStockItems = array_slice($lowStockItems, 0, 10);
            
            return response()->json([
                'success' => true,
                'items' => $lowStockItems
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching low stock items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'items' => []
            ], 500);
        }
    }
    
    public function getBoutiqueRentReminders()
    {
        try {
            $reminders = [];
            $now = Carbon::now();
            
            // Get all active boutiques
            $boutiques = Boutique::where('status', '1') // Active boutiques
                ->whereNotNull('monthly_rent')
                ->whereNotNull('rent_date')
                ->get();
            
            foreach ($boutiques as $boutique) {
                $rentDate = Carbon::parse($boutique->rent_date);
                $monthlyRent = floatval($boutique->monthly_rent ?? 0);
                
                if ($monthlyRent <= 0) {
                    continue;
                }
                
                // Calculate next due date (same day of month)
                $nextDueDate = Carbon::create($now->year, $now->month, $rentDate->day);
                
                // If the day has passed this month, move to next month
                if ($nextDueDate->lt($now)) {
                    $nextDueDate->addMonth();
                }
                
                $daysUntilDue = $now->diffInDays($nextDueDate, false);
                
                // Check if there's an unpaid invoice for current or previous months
                $currentMonth = $now->format('Y-m');
                $unpaidInvoice = BoutiqueInvo::where('boutique_id', (string)$boutique->id)
                    ->where('status', '5') // 5 = unpaid
                    ->orderBy('month', 'DESC')
                    ->first();
                
                // Check if boutique has unpaid rent status
                $hasUnpaidRent = ($boutique->rent_invoice_status == '5');
                
                // Determine status
                $status = 'paid';
                $statusClass = 'ok';
                $statusText = trans('messages.paid', [], session('locale'));
                
                // If there's an unpaid invoice or boutique status is unpaid
                if ($unpaidInvoice || $hasUnpaidRent) {
                    if ($daysUntilDue < 0) {
                        // Past due
                        $status = 'late';
                        $statusClass = 'danger';
                        $statusText = trans('messages.late', [], session('locale'));
                    } else if ($daysUntilDue <= 7) {
                        // Due soon (within 7 days)
                        $status = 'soon';
                        $statusClass = 'warn';
                        $statusText = trans('messages.soon', [], session('locale'));
                    } else {
                        // Unpaid but not due yet
                        $status = 'unpaid';
                        $statusClass = 'warn';
                        $statusText = trans('messages.unpaid', [], session('locale'));
                    }
                } else {
                    // Check if due date is within 7 days (even if paid, show as reminder)
                    if ($daysUntilDue <= 7 && $daysUntilDue >= 0) {
                        $status = 'soon';
                        $statusClass = 'warn';
                        $statusText = trans('messages.soon', [], session('locale'));
                    }
                }
                
                // Include if unpaid, late, or due soon (within 7 days)
                if ($status !== 'paid') {
                    $reminders[] = [
                        'id' => $boutique->id,
                        'boutique_name' => $boutique->boutique_name,
                        'amount' => $monthlyRent,
                        'due_date' => $nextDueDate->format('Y-m-d'),
                        'due_date_formatted' => $nextDueDate->format('d/m/Y'),
                        'days_until_due' => $daysUntilDue,
                        'status' => $status,
                        'status_class' => $statusClass,
                        'status_text' => $statusText,
                    ];
                }
            }
            
            // Sort by due date (earliest first)
            usort($reminders, function($a, $b) {
                return strtotime($a['due_date']) <=> strtotime($b['due_date']);
            });
            
            return response()->json([
                'success' => true,
                'reminders' => $reminders
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching boutique rent reminders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'reminders' => []
            ], 500);
        }
    }
    
    public function getRecentSpecialOrders()
    {
        try {
            $orders = SpecialOrder::with(['customer', 'items.stock.images'])
                ->orderBy('created_at', 'DESC')
                ->limit(6)
                ->get()
                ->map(function($order) {
                    $customer = $order->customer;
                    $governorate = optional($customer)->governorate ?? '';
                    $city = optional($customer)->area ?? '';
                    $location = trim($governorate . ($city ? ' - ' . $city : ''));
                    
                    // Generate order number: YYYY-00ID (e.g., 2025-0001)
                    $orderDate = Carbon::parse($order->created_at);
                    $orderNumber = $orderDate->format('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                    
                    // Get first item image or placeholder
                    $firstItem = $order->items->first();
                    $image = asset('images/placeholder.png');
                    if ($firstItem && $firstItem->stock && $firstItem->stock->images->first()) {
                        $imagePath = $firstItem->stock->images->first()->image_path;
                        if (strpos($imagePath, 'http') === 0) {
                            $image = $imagePath;
                        } else {
                            $image = asset($imagePath);
                        }
                    }
                    
                    // Format date
                    $createdAt = Carbon::parse($order->created_at);
                    $now = Carbon::now();
                    $dateFormatted = '';
                    
                    if ($createdAt->isToday()) {
                        $dateFormatted = trans('messages.today', [], session('locale')) . ' ' . $createdAt->format('h:i A');
                    } elseif ($createdAt->isYesterday()) {
                        $dateFormatted = trans('messages.yesterday', [], session('locale')) . ' ' . $createdAt->format('h:i A');
                    } else {
                        $dateFormatted = $createdAt->format('Y-m-d h:i A');
                    }
                    
                    // Get status badge info
                    $statusInfo = $this->getStatusInfo($order->status);
                    
                    // Get source badge info
                    $sourceInfo = $this->getSourceInfo($order->source);
                    
                    return [
                        'id' => $order->id,
                        'order_no' => $orderNumber,
                        'customer_name' => optional($customer)->name ?? 'N/A',
                        'customer_phone' => optional($customer)->phone ?? '—',
                        'location' => $location,
                        'status' => $order->status,
                        'status_info' => $statusInfo,
                        'source' => $order->source,
                        'source_info' => $sourceInfo,
                        'total' => floatval($order->total_amount ?? 0),
                        'paid' => floatval($order->paid_amount ?? 0),
                        'remaining' => floatval($order->total_amount ?? 0) - floatval($order->paid_amount ?? 0),
                        'date' => $order->created_at->format('Y-m-d'),
                        'date_formatted' => $dateFormatted,
                        'image' => $image,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'orders' => $orders
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching recent special orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'orders' => []
            ], 500);
        }
    }
    
    private function getStatusInfo($status)
    {
        $statusMap = [
            'new' => [
                'text' => trans('messages.new_orders', [], session('locale')),
                'class' => 'background: rgba(245,158,11,.14); color: var(--warn);',
                'icon' => 'hourglass_top'
            ],
            'processing' => [
                'text' => trans('messages.in_progress', [], session('locale')),
                'class' => 'background: rgba(109,91,208,.12); color: var(--primary2);',
                'icon' => 'content_cut'
            ],
            'ready' => [
                'text' => trans('messages.ready_for_delivery', [], session('locale')) ?? 'Ready',
                'class' => 'background: rgba(16,185,129,.12); color: var(--ok);',
                'icon' => 'check_circle'
            ],
            'delivered' => [
                'text' => trans('messages.delivered', [], session('locale')),
                'class' => 'background: rgba(16,185,129,.12); color: var(--ok);',
                'icon' => 'local_shipping'
            ],
            'partially_ready' => [
                'text' => trans('messages.partially_ready', [], session('locale')) ?? 'Partially Ready',
                'class' => 'background: rgba(109,91,208,.12); color: var(--primary2);',
                'icon' => 'hourglass_empty'
            ],
        ];
        
        return $statusMap[$status] ?? [
            'text' => ucfirst($status),
            'class' => 'background: rgba(107,114,128,.12); color: var(--muted);',
            'icon' => 'info'
        ];
    }
    
    private function getSourceInfo($source)
    {
        $sourceMap = [
            'whatsapp' => [
                'text' => trans('messages.whatsapp', [], session('locale')),
                'class' => 'background: rgba(179,75,138,.12); color: var(--primary);',
                'icon' => 'chat'
            ],
            'walkin' => [
                'text' => trans('messages.walk_in', [], session('locale')) ?? 'Walk-in',
                'class' => 'background: rgba(109,91,208,.12); color: var(--primary2);',
                'icon' => 'language'
            ],
        ];
        
        return $sourceMap[$source] ?? [
            'text' => ucfirst($source),
            'class' => 'background: rgba(107,114,128,.12); color: var(--muted);',
            'icon' => 'info'
        ];
    }
}
