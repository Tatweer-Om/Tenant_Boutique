<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index()
    {
        $tenants = Tenant::with('domains')->paginate(10); // pagination, 10 per page
        return view('tenants.tenants', compact('tenants'));
    }

    /**
     * Show the form for creating a new tenant.
     */
    public function create()
    {
        return view('tenants.create');
    }

    /**
     * Store a newly created tenant.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string',
            'contact' => 'required|string', 
            'domain' => 'required|string|max:255|unique:domains,domain',
            'password' => 'required|min:6',
        ]);
    
        $tenant = Tenant::create($validateData);
        

        $tenant->domains()->create([
            'domain' => $validateData['domain'].'.'.config('app.domain')
        ]);
        return redirect()->route('tenants.index');
    }

    /**
     * Show a tenant.
     */
    public function show(Tenant $tenant)
    {
        return view('tenants.show', compact('tenant'));
    }

    /**
     * Show the form for editing a tenant.
     */
    public function edit(Tenant $tenant)
    {
        return view('tenants.edit', compact('tenant'));
    }

    /**
     * Update a tenant.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,email',
            'domain' => 'required|unique:tenants,domain',
            'password' => 'required|string|min:6',
        ]);
        if($request->password)
        {
            $request->validate([
                'password' => 'required|string|min:6|confirmed',
            ]);
        }

        $tenant->update($request->all());

        return redirect()->route('tenants.tenants')->with('success', 'Tenant updated successfully!');
    }

    /**
     * Delete a tenant.
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return redirect()->route('tenants.tenants')->with('success', 'Tenant deleted successfully!');
    }
}
