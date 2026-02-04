<?php

namespace Modules\Customer\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Area;
use App\Models\City;
use App\Models\PosOrders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $areas = Area::orderBy('area_name_ar', 'ASC')->get(['id', 'area_name_ar', 'area_name_en']);
        $cities = City::orderBy('city_name_ar', 'ASC')->get(['id', 'city_name_ar', 'city_name_en', 'area_id']);

        return view('customer::customer', compact('areas', 'cities'));
    }

    public function getCustomers()
    {
        return Customer::with(['city', 'area'])
            ->orderBy('id', 'DESC')
            ->paginate(10);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'notes' => 'nullable|string',
        ]);

        $customer = new Customer();
        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->city_id = $request->city_id;
        $customer->area_id = $request->area_id;
        $customer->notes = $request->notes;
        $customer->save();

        $customer->load(['city', 'area']);

        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'notes' => 'nullable|string',
        ]);

        $customer->name = $request->name;
        $customer->phone = $request->phone;
        $customer->city_id = $request->city_id;
        $customer->area_id = $request->area_id;
        $customer->notes = $request->notes;
        $customer->save();

        $customer->load(['city', 'area']);

        return response()->json($customer);
    }

    public function show(Customer $customer)
    {
        $customer->load(['city', 'area']);
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function profile($id)
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $customer = Customer::with(['city', 'area', 'specialOrders.items.stock.images', 'specialOrders.items.tailor'])
            ->findOrFail($id);

        $specialOrders = $customer->specialOrders()->with(['items.stock.images', 'items.tailor'])->get();

        $posOrders = PosOrders::where('customer_id', (string)$customer->id)
            ->with(['details.stock.images', 'details.color', 'details.size'])
            ->orderBy('created_at', 'DESC')
            ->get();

        $specialOrdersTotalRevenue = $specialOrders->sum('total_amount');
        $specialOrdersTotalItems = $specialOrders->sum(function ($order) {
            return $order->items->sum('quantity');
        });
        $specialOrdersCount = $specialOrders->count();

        $posOrdersTotalRevenue = $posOrders->sum('total_amount');
        $posOrdersTotalItems = $posOrders->sum('item_count');
        $posOrdersCount = $posOrders->count();

        $totalRevenue = $specialOrdersTotalRevenue + $posOrdersTotalRevenue;
        $totalItems = $specialOrdersTotalItems + $posOrdersTotalItems;

        return view('customer::customer_profile', compact(
            'customer',
            'specialOrders',
            'posOrders',
            'specialOrdersTotalRevenue',
            'specialOrdersTotalItems',
            'specialOrdersCount',
            'posOrdersTotalRevenue',
            'posOrdersTotalItems',
            'posOrdersCount',
            'totalRevenue',
            'totalItems'
        ));
    }
}
