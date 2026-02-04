<?php

namespace Modules\Setting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Setting\Models\Setting;
use Illuminate\Support\Facades\File;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::getSettings();
      
        return view('setting::index', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = Setting::getSettings();

        // Update company information
        $settings->company_name = $request->input('company_name');
        $settings->company_email = $request->input('company_email');
        $settings->company_cr_no = $request->input('company_cr_no');
        $settings->company_address = $request->input('company_address');
        
        // Handle logo upload
        if ($request->hasFile('company_logo')) {
            $folderPath = public_path('images/company_logo');
            
            if (!File::isDirectory($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }
            
            // Delete old logo if exists
            if ($settings->company_logo && File::exists(public_path('images/company_logo/' . $settings->company_logo))) {
                File::delete(public_path('images/company_logo/' . $settings->company_logo));
            }
            
            $logo = $request->file('company_logo');
            $logoName = time() . '_' . uniqid() . '.' . $logo->getClientOriginalExtension();
            $logo->move($folderPath, $logoName);
            $settings->company_logo = $logoName;
        }
        
        // Update late delivery weeks
        $settings->late_delivery_weeks = $request->input('late_delivery_weeks', 2);
        
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => trans('messages.settings_updated_successfully', [], session('locale')),
            'settings' => [
                'company_name' => $settings->company_name,
                'company_email' => $settings->company_email,
                'company_cr_no' => $settings->company_cr_no,
                'company_logo' => $settings->company_logo ? asset('images/company_logo/' . $settings->company_logo) : null,
                'company_address' => $settings->company_address,
                'late_delivery_weeks' => $settings->late_delivery_weeks,
            ]
        ]);
    }

    public function getSettings()
    {
        $settings = Setting::getSettings();
        return response()->json([
            'success' => true,
            'settings' => [
                'company_name' => $settings->company_name,
                'company_email' => $settings->company_email,
                'company_cr_no' => $settings->company_cr_no,
                'company_logo' => $settings->company_logo ? asset('images/company_logo/' . $settings->company_logo) : null,
                'company_address' => $settings->company_address,
                'late_delivery_weeks' => $settings->late_delivery_weeks,
            ]
        ]);
    }
}
