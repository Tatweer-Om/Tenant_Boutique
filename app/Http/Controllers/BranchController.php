<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Branch;
use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index(){

    return view ('branch.branch');

    }

    public function show_branch()
    {

        $sno=0;

        $view_authbranch= Branch::all();
        if(count($view_authbranch)>0)
        {
            foreach($view_authbranch as $value)
            {

                $branch_name='<a class-"patient-info ps-0" href="javascript:void(0);">'.$value->branch_name.'</a>';

                $modal = '
                <a href="javascript:void(0);" class="me-3 edit-staff" data-bs-toggle="modal" data-bs-target="#add_branch_modal" onclick=edit("'.$value->id.'")>
                    <i class="fa fa-pencil fs-18 text-success"></i>
                </a>
                <a href="javascript:void(0);" onclick=del("'.$value->id.'")>
                    <i class="fa fa-trash fs-18 text-danger"></i>
                </a>';

                $add_data=Carbon::parse($value->created_at)->format('d-m-Y (h:i a)');




                $sno++;
                $json[] = array(
                    '<span class="patient-info ps-0">'. $sno . '</span>',
                    '<span class="text-nowrap ms-2">' . $branch_name . '</span>',
                    '<span class="text-primary">' . $value->branch_phone . '</span>',
                    '<span >' . $value->branch_email . '</span>',
                    '<span >' . $value->added_by . '</span>',
                    '<span >' . $add_data . '</span>',
                    $modal
                );

            }
            $response = array();
            $response['success'] = true;
            $response['aaData'] = $json;
            echo json_encode($response);
        }
        else
        {
            $response = array();
            $response['sEcho'] = 0;
            $response['iTotalRecords'] = 0;
            $response['iTotalDisplayRecords'] = 0;
            $response['aaData'] = [];
            echo json_encode($response);
        }
    }

    public function add_branch(Request $request){

        $user_id = Auth::id();
        $data= User::where('id', $user_id )->first();
        $user_name= $data->user_name;



        $branch = new Branch();

        $branch->branch_name = $request['branch_name'];
        $branch->branch_email = $request['branch_email'];
        $branch->branch_phone = $request['branch_phone'];
        $branch->notes = $request['notes'];
        $branch->added_by = $user_name;
        $branch->user_id = $user_id;
        $branch->save();
        return response()->json(['branch_id' => $branch->id]);

    }


    public function edit_branch(Request $request){

        $branch_id = $request->input('id');

        $branch_data = Branch::where('id', $branch_id)->first();
        $data = [
            'branch_id' => $branch_data->id,
            'branch_name' => $branch_data->branch_name,
            'branch_phone' => $branch_data->branch_phone,
            'branch_email' => $branch_data->branch_email,
            'notes' => $branch_data->notes,
            // Add more attributes as needed
        ];

        return response()->json($data);
    }

    public function update_branch(Request $request)
{
    $branch_id = $request->input('branch_id');
    $user_id = Auth::id();

    $user = User::where('id', $user_id)->first();
    $user_name = $user->user_name;

    $branch = Branch::where('id', $branch_id)->first();

    if (!$branch) {
        return response()->json(['error' => trans('messages.branch_not_found', [], session('locale'))], 404);
    }

    $previousData = $branch->only(['branch_name', 'branch_email', 'branch_phone', 'notes', 'added_by', 'user_id', 'created_at']);

    $branch->branch_name = $request->input('branch_name');
    $branch->branch_email = $request->input('branch_email');
    $branch->branch_phone = $request->input('branch_phone');
    $branch->notes = $request->input('notes');
    $branch->added_by = $user_name;
    $branch->user_id = $user_id;
    $branch->save();

    $history = new History();
    $history->user_id = $user_id;
    $history->table_name = 'branches';
    $history->function = 'update';
    $history->function_status = 1;
    $history->branch_id = $branch_id;
    $history->record_id = $branch->id;
    $history->previous_data = json_encode($previousData);
    $history->updated_data = json_encode($branch->only([
        'branch_name', 'branch_email', 'branch_phone', 'notes', 'added_by', 'user_id'
    ]));
    $history->added_by = $user_name;
    $history->save();

    return response()->json([trans('messages.success_lang', [], session('locale')) => trans('messages.user_update_lang', [], session('locale'))]);
}


public function delete_branch(Request $request) {


    $user_id = Auth::id();
    $user = User::where('id', $user_id)->first();
    $user_name = $user->user_name;
    $branch_id = $request->input('id');
    $branch = Branch::where('id', $branch_id)->first();

    if (!$branch) {
        return response()->json([trans('messages.error_lang', [], session('locale')) => trans('messages.branch_not_found', [], session('locale'))], 404);
    }

    $previousData = $branch->only([
        'branch_name', 'branch_email', 'branch_phone', 'notes', 'added_by', 'user_id', 'created_at'
    ]);

    $currentUser = Auth::user();
    $username = $currentUser->user_name;
    $branch_id = $currentUser->branch_id;

    $history = new History();
    $history->user_id = $user_id;
    $history->table_name = 'branches';
    $history->function = 'delete';
    $history->function_status = 2;
    $history->branch_id = $branch_id;
    $history->record_id = $branch->id;
    $history->previous_data = json_encode($previousData);

    $history->added_by = $user_name;
    $history->save();
    $branch->delete();

    return response()->json([
        trans('messages.success_lang', [], session('locale')) => trans('messages.user_deleted_lang', [], session('locale'))
    ]);
}
}
