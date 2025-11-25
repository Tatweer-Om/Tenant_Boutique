<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
     public function index()
    {
        return view('users.user');
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
        $user->added_by = 'system';
        $user->user_id = 1;
        $user->save();

        return response()->json($user);
    }

  public function update(Request $request, User $user)
{
    $user->user_name   = $request->user_name;
    $user->user_phone  = $request->user_phone;
    $user->user_address = $request->user_address;
    $user->updated_by  = 'system_update';

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
}
