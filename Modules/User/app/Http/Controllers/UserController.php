<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
     public function index()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(1, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }

        return view('user::user');
    }

    public function getusers()
    {
        return User::orderBy('id', 'DESC')->paginate(10);
    }


    public function store(Request $request)
    {

        $user = new User();
        $user->user_name = $request->user_name;
        $user->user_phone = $request->user_phone;
        $user->user_email = $request->user_email;
        $user->password = Hash::make($request->user_password);
        $user->notes = $request->notes;
        // Convert permissions to integers (numeric IDs: 1-12)
        $permissions = $request->permissions ?? [];
        $user->permissions = array_map('intval', $permissions);
        $user->added_by = 'system';
        $user->user_id = Auth::guard('tenant')->id() ?? 1;
        $user->save();

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $user->user_name   = $request->user_name;
        $user->user_phone  = $request->user_phone;
        $user->user_email  = $request->user_email;
        $user->notes       = $request->notes;
        // Convert permissions to integers (numeric IDs: 1-12)
        $permissions = $request->permissions ?? [];
        $user->permissions = array_map('intval', $permissions);
        $user->updated_by  = auth()->user()->user_name ?? 'system_update';

        // Update password ONLY if the user enters a new one
        if (!empty($request->user_password)) {
            $user->password = Hash::make($request->user_password);
        }

        $user->save();

        return response()->json($user);
    }


    public function show(user $user)
    {
        return response()->json($user);
    }

    public function destroy(user $user)
    {
        $user->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function tlogin_page(Request $request)
    {
        return view('user::login_page');
    }

    public function tlogin_user(Request $request)
    {
        // Validate input
        $request->validate([
            'user_phone' => 'required',
            'password'   => 'required',
        ]);

        // Try to find user by username OR phone
        $user = User::where('user_name', $request->user_phone)
                    ->orWhere('user_phone', $request->user_phone)
                    ->first();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'اسم المستخدم أو رقم الهاتف غير صحيح',
            ]);
        }

        // Check password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'كلمة المرور غير صحيحة',
            ]);
        }

        // Login user
        auth()->guard('tenant')->login($user); // important
        $request->session()->regenerate();
        session(['locale' => 'en']);
        return response()->json([
            'status'  => 'success',
            'message' => 'تم تسجيل الدخول بنجاح',
            'redirect_url' => route('user'), // send route to frontend
        ]);
        
    }

    public function tlogout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('tlogin_page')->with('success', 'تم تسجيل الخروج بنجاح');
    }
}
