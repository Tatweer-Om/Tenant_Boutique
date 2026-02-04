<?php

namespace Modules\Account\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Account\Models\Account;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function index()
    {
        if (!Auth::guard('tenant')->check()) {
            return redirect()->route('tlogin_page')->with('error', 'Please login first');
        }

        $permissions = Auth::guard('tenant')->user()->permissions ?? [];

        if (!in_array(2, $permissions)) {
            return redirect()->route('tlogin_page')->with('error', 'Permission denied');
        }
         

        return view('account::account');
    }

    public function getAccounts()
    {
        return Account::orderBy('id', 'DESC')->paginate(10);
    }

    /**
     * Return all accounts (for dropdowns / payment)
     */
    public function all()
    {
        return response()->json(
            Account::orderBy('account_name', 'ASC')
                ->get(['id', 'account_name', 'account_branch', 'account_no'])
        );
    }

    public function store(Request $request)
    {
        $user = Auth::guard('tenant')->user();

        $account = new Account();
        $account->account_name = $request->account_name;
        $account->account_branch = $request->account_branch;
        $account->account_no = $request->account_no;
        $account->opening_balance = $request->opening_balance ?? 0;
        $account->commission = $request->commission ?? 0;
        $account->account_type = $request->account_type;
        $account->notes = $request->notes;
        $account->account_status = $request->account_status ?? 1;
        $account->added_by = $user->user_name ?? 'system';
        $account->user_id = $user->id ?? 1;

        $account->save();

        return response()->json($account);
    }

    public function update(Request $request, Account $account)
    {
        $user = Auth::guard('tenant')->user();

        $account->account_name = $request->account_name;
        $account->account_branch = $request->account_branch;
        $account->account_no = $request->account_no;
        $account->opening_balance = $request->opening_balance ?? 0;
        $account->commission = $request->commission ?? 0;
        $account->account_type = $request->account_type;
        $account->notes = $request->notes;
        $account->account_status = $request->account_status ?? 1;
        $account->updated_by = $user->user_name ?? 'system_update';
        $account->save();

        return response()->json($account);
    }

    public function show(Account $account)
    {
        return response()->json($account);
    }

    public function destroy(Account $account)
    {
        $account->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
