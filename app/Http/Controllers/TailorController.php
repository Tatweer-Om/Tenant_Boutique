<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Tailor;
use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
