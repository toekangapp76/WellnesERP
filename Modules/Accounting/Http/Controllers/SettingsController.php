<?php

namespace Modules\Accounting\Http\Controllers;

use App\Business;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingAccountType;
use Modules\Accounting\Entities\AccountingAccTransMapping;
use Modules\Accounting\Entities\AccountingBudget;
use Modules\Accounting\Utils\AccountingUtil;
use App\BusinessLocation;
use App\ExpenseCategory;

class SettingsController extends Controller
{
    protected $accountingUtil;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(AccountingUtil $accountingUtil, ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->accountingUtil = $accountingUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') ||
            $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module'))) {
            abort(403, 'Unauthorized action.');
        }

        $account_sub_types = AccountingAccountType::where('account_type', 'sub_type')
                                    ->where(function ($q) use ($business_id) {
                                        $q->whereNull('business_id')
                                        ->orWhere('business_id', $business_id);
                                    })
                                    ->get();

        $account_types = AccountingAccountType::accounting_primary_type();

        $accounting_settings = $this->accountingUtil->getAccountingSettings($business_id);

        $business_locations = BusinessLocation::where('business_id', $business_id)->get();

         $expence_categories = ExpenseCategory::where('business_id', $business_id)->get();

        $default_depreciation_expense_account = ! empty($accounting_settings['default_depreciation_expense_account_id'])
            ? AccountingAccount::find($accounting_settings['default_depreciation_expense_account_id']) : null;
        $default_accumulated_depreciation_account = ! empty($accounting_settings['default_accumulated_depreciation_account_id'])
            ? AccountingAccount::find($accounting_settings['default_accumulated_depreciation_account_id']) : null;
        $default_disposal_gain_loss_account = ! empty($accounting_settings['default_disposal_gain_loss_account_id'])
            ? AccountingAccount::find($accounting_settings['default_disposal_gain_loss_account_id']) : null;

        return view('accounting::settings.index')->with(compact('account_sub_types', 'account_types',
            'accounting_settings', 'business_locations', 'expence_categories',
            'default_depreciation_expense_account', 'default_accumulated_depreciation_account',
            'default_disposal_gain_loss_account'));
    }

    public function resetData()
    {
        // Temporarily disabled: this action irreversibly deletes ALL accounting
        // accounts, transactions, custom account types, and budget/mapping links
        // for the business, via a plain GET request with only a client-side
        // confirm() dialog and no server-side safeguard. It was triggered
        // accidentally on 2026-07-24 and wiped the business's chart of accounts
        // and opening balances (recovered from the MySQL binlog). Disabled until
        // a proper confirmation (e.g. typed business name/password + POST) is added.
        return back()->with('error', 'Fitur Reset Data sementara dinonaktifkan untuk mencegah kehilangan data. Hubungi developer jika memang perlu mereset data akuntansi.');

        // phpcs:disable
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') ||
            $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module'))) {
            abort(403, 'Unauthorized action.');
        }

        //check for admin
        if (! $this->accountingUtil->is_admin(auth()->user())) {
            abort(403, 'Unauthorized action.');
        }

        //reset logic
        AccountingBudget::join('accounting_accounts', 'accounting_budgets.accounting_account_id', '=', 'accounting_accounts.id')
            ->where('accounting_accounts.business_id', $business_id)
            ->delete();

        AccountingAccountType::where('business_id', $business_id)
            ->delete();

        AccountingAccTransMapping::where('business_id', $business_id)->delete();

        AccountingAccountsTransaction::join('accounting_accounts', 'accounting_accounts_transactions.accounting_account_id', '=', 'accounting_accounts.id')
            ->where('business_id', $business_id)->delete();

        AccountingAccount::where('business_id', $business_id)->delete();

        return back();
        // phpcs:enable
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('accounting::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function saveSettings(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || ($this->moduleUtil->hasThePermissionInSubscription($business_id,
        'accounting_module')))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $accounting_settings = $request->only([
                'journal_entry_prefix', 'transfer_prefix', 'accounting_default_map',
                'fixed_asset_prefix', 'depreciation_prefix',
                'default_depreciation_expense_account_id', 'default_accumulated_depreciation_account_id',
                'default_disposal_gain_loss_account_id',
            ]);

            Business::where('id', $business_id)
                        ->update(['accounting_settings' => json_encode($accounting_settings)]);
            
            //Update accounting_default_map for each locations
            $accounting_default_map = $request->get('accounting_default_map');
            foreach($accounting_default_map as $location_id => $details){
                BusinessLocation::where('id', $location_id)
                    ->update(['accounting_default_map' => json_encode($details)]);
            }

            $output = ['success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return view('accounting::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return view('accounting::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
