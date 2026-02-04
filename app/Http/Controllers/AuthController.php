<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Show login form (if using blade, or return JSON if API).
     */
    public function showLogin()
    {
        return view('login'); // or return response()->json(['message' => 'Login endpoint']);
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $credentials = $request->only('name', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended('central_dashboard'); 
            // or return response()->json(['user' => Auth::user()]);
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('showLogin'); 
        // or return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Register new user (optional)
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
        ]);

        Auth::login($user);

        return redirect('central_dashboard'); 
        // or return response()->json(['user' => $user]);
    }
}
