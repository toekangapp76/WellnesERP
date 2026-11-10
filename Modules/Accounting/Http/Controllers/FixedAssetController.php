<?php

namespace Modules\Accounting\Http\Controllers;

use App\BusinessLocation;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\AccountingFixedAsset;
use Modules\Accounting\Entities\AccountingFixedAssetDepreciation;
use Modules\Accounting\Utils\AccountingUtil;
use Yajra\DataTables\Facades\DataTables;

class FixedAssetController extends Controller
{
    protected $util;

    public function __construct(Util $util, ModuleUtil $moduleUtil, AccountingUtil $accountingUtil)
    {
        $this->util = $util;
        $this->moduleUtil = $moduleUtil;
        $this->accountingUtil = $accountingUtil;
    }

    private function checkAccess($permission)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') ||
            $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! auth()->user()->can($permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $business_id;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $business_id = $this->checkAccess('accounting.view_fixed_asset');

        if (request()->ajax()) {
            $assets = AccountingFixedAsset::where('business_id', $business_id)
                ->with(['location'])
                ->select(['id', 'asset_code', 'name', 'category', 'location_id', 'purchase_date',
                    'purchase_cost', 'accumulated_depreciation', 'salvage_value', 'status']);

            if (! empty(request()->status)) {
                $assets->where('status', request()->status);
            }

            return Datatables::of($assets)
                ->addColumn('location_name', function ($row) {
                    return ! empty($row->location) ? $row->location->name : '-';
                })
                ->editColumn('purchase_date', function ($row) {
                    return $this->util->format_date($row->purchase_date);
                })
                ->editColumn('purchase_cost', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true" data-orig-value="'.$row->purchase_cost.'">'.$row->purchase_cost.'</span>';
                })
                ->addColumn('book_value', function ($row) {
                    $book_value = max($row->salvage_value, $row->purchase_cost - $row->accumulated_depreciation);

                    return '<span class="display_currency" data-currency_symbol="true" data-orig-value="'.$book_value.'">'.$book_value.'</span>';
                })
                ->editColumn('accumulated_depreciation', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true" data-orig-value="'.$row->accumulated_depreciation.'">'.$row->accumulated_depreciation.'</span>';
                })
                ->editColumn('status', function ($row) {
                    $badges = [
                        'active' => '<span class="badge bg-green">'.__('accounting::lang.status_active').'</span>',
                        'fully_depreciated' => '<span class="badge bg-yellow">'.__('accounting::lang.status_fully_depreciated').'</span>',
                        'disposed' => '<span class="badge bg-gray">'.__('accounting::lang.status_disposed').'</span>',
                    ];

                    return $badges[$row->status] ?? $row->status;
                })
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                data-toggle="dropdown" aria-expanded="false">'.
                                __('messages.actions').
                                '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right" role="menu">';
                    $html .= '<li><a href="'.action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'show'], [$row->id]).'">
                                <i class="fas fa-eye"></i> '.__('messages.view').'</a></li>';

                    if (auth()->user()->can('accounting.edit_fixed_asset')) {
                        $html .= '<li><a href="'.action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'edit'], [$row->id]).'">
                                    <i class="fas fa-edit"></i> '.__('messages.edit').'</a></li>';
                    }

                    if ($row->status !== 'disposed' && auth()->user()->can('accounting.dispose_fixed_asset')) {
                        $html .= '<li><a href="#" data-href="'.action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'disposeForm'], [$row->id]).'"
                                    class="btn-modal" data-container="#dispose_asset_modal">
                                    <i class="fas fa-box"></i> '.__('accounting::lang.dispose_asset').'</a></li>';
                    }

                    if (auth()->user()->can('accounting.delete_fixed_asset')) {
                        $html .= '<li><a href="#" data-href="'.action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'destroy'], [$row->id]).'"
                                    class="delete_fixed_asset_button"><i class="fas fa-trash"></i> '.__('messages.delete').'</a></li>';
                    }

                    $html .= '</ul></div>';

                    return $html;
                })
                ->rawColumns(['status', 'action', 'purchase_cost', 'book_value', 'accumulated_depreciation'])
                ->make(true);
        }

        return view('accounting::fixed_asset.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $business_id = $this->checkAccess('accounting.add_fixed_asset');

        $locations = BusinessLocation::forDropdown($business_id);
        $accounting_settings = $this->accountingUtil->getAccountingSettings($business_id);

        return view('accounting::fixed_asset.create')
            ->with(compact('locations', 'accounting_settings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $business_id = $this->checkAccess('accounting.add_fixed_asset');

        $request->validate([
            'name' => 'required|string|max:191',
            'purchase_date' => 'required',
            'purchase_cost' => 'required|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $user_id = $request->session()->get('user.id');

            $asset_code = $request->get('asset_code');
            if (empty($asset_code)) {
                $accounting_settings = $this->accountingUtil->getAccountingSettings($business_id);
                $ref_count = $this->util->setAndGetReferenceCount('fixed_asset', $business_id);
                $prefix = ! empty($accounting_settings['fixed_asset_prefix']) ? $accounting_settings['fixed_asset_prefix'] : 'FA-';
                $asset_code = $this->util->generateReferenceNumber('fixed_asset', $ref_count, $business_id, $prefix);
            }

            $purchase_date = $this->util->uf_date($request->get('purchase_date'));
            $depreciation_start_date = ! empty($request->get('depreciation_start_date'))
                ? $this->util->uf_date($request->get('depreciation_start_date'))
                : $purchase_date;

            $asset = new AccountingFixedAsset();
            $asset->business_id = $business_id;
            $asset->location_id = $request->get('location_id') ?: null;
            $asset->asset_code = $asset_code;
            $asset->name = $request->get('name');
            $asset->category = $request->get('category');
            $asset->description = $request->get('description');
            $asset->purchase_date = $purchase_date;
            $asset->purchase_cost = $this->util->num_uf($request->get('purchase_cost'));
            $asset->salvage_value = $this->util->num_uf($request->get('salvage_value', 0));
            $asset->useful_life_months = $request->get('useful_life_months');
            $asset->depreciation_method = 'straight_line';
            $asset->depreciation_start_date = $depreciation_start_date;
            $asset->asset_account_id = $request->get('asset_account_id') ?: null;
            $asset->depreciation_expense_account_id = $request->get('depreciation_expense_account_id') ?: null;
            $asset->accumulated_depreciation_account_id = $request->get('accumulated_depreciation_account_id') ?: null;
            $asset->notes = $request->get('notes');
            $asset->created_by = $user_id;
            $asset->save();

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.added_success')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        if ($request->ajax()) {
            return $output;
        }

        return redirect()->action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Show the specified resource: detail + depreciation schedule.
     */
    public function show($id)
    {
        $business_id = $this->checkAccess('accounting.view_fixed_asset');

        $asset = AccountingFixedAsset::where('business_id', $business_id)
            ->with(['location', 'asset_account', 'depreciation_expense_account', 'accumulated_depreciation_account'])
            ->findOrFail($id);

        $depreciations = AccountingFixedAssetDepreciation::where('fixed_asset_id', $id)
            ->orderBy('period_date')
            ->get();

        return view('accounting::fixed_asset.show')
            ->with(compact('asset', 'depreciations'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $business_id = $this->checkAccess('accounting.edit_fixed_asset');

        $asset = AccountingFixedAsset::where('business_id', $business_id)->findOrFail($id);
        $locations = BusinessLocation::forDropdown($business_id);
        $accounting_settings = $this->accountingUtil->getAccountingSettings($business_id);
        $is_locked = $asset->hasPostedDepreciation();

        return view('accounting::fixed_asset.edit')
            ->with(compact('asset', 'locations', 'accounting_settings', 'is_locked'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $business_id = $this->checkAccess('accounting.edit_fixed_asset');

        try {
            $asset = AccountingFixedAsset::where('business_id', $business_id)->findOrFail($id);
            $is_locked = $asset->hasPostedDepreciation();

            DB::beginTransaction();

            $asset->location_id = $request->get('location_id') ?: null;
            $asset->name = $request->get('name');
            $asset->category = $request->get('category');
            $asset->description = $request->get('description');
            $asset->asset_account_id = $request->get('asset_account_id') ?: null;
            $asset->depreciation_expense_account_id = $request->get('depreciation_expense_account_id') ?: null;
            $asset->accumulated_depreciation_account_id = $request->get('accumulated_depreciation_account_id') ?: null;
            $asset->notes = $request->get('notes');

            // Field finansial (harga perolehan, nilai residu, umur ekonomis, tanggal mulai
            // penyusutan) dikunci begitu ada penyusutan yang sudah diposting, supaya riwayat
            // jurnal yang sudah tercatat tidak jadi tidak konsisten dengan data aset.
            if (! $is_locked) {
                $asset->purchase_date = $this->util->uf_date($request->get('purchase_date'));
                $asset->purchase_cost = $this->util->num_uf($request->get('purchase_cost'));
                $asset->salvage_value = $this->util->num_uf($request->get('salvage_value', 0));
                $asset->useful_life_months = $request->get('useful_life_months');
                $asset->depreciation_start_date = ! empty($request->get('depreciation_start_date'))
                    ? $this->util->uf_date($request->get('depreciation_start_date'))
                    : $asset->purchase_date;
            }

            $asset->save();

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.updated_success')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        if ($request->ajax()) {
            return $output;
        }

        return redirect()->action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Remove the specified resource, only when it has no accounting history yet.
     */
    public function destroy($id)
    {
        $business_id = $this->checkAccess('accounting.delete_fixed_asset');

        $asset = AccountingFixedAsset::where('business_id', $business_id)->findOrFail($id);

        if ($asset->hasPostedDepreciation() || $asset->status === 'disposed') {
            return ['success' => 0, 'msg' => __('accounting::lang.cannot_delete_posted_asset')];
        }

        $asset->delete();

        return ['success' => 1, 'msg' => __('lang_v1.deleted_success')];
    }

    /**
     * Preview + form for a batch depreciation run.
     */
    public function runDepreciationForm()
    {
        $business_id = $this->checkAccess('accounting.run_depreciation');

        $period_date = ! empty(request()->period_date)
            ? $this->util->uf_date(request()->period_date)
            : \Carbon::now()->endOfMonth()->format('Y-m-d');

        $eligible = $this->eligibleAssetsForPeriod($business_id, $period_date);

        return view('accounting::fixed_asset.run_depreciation')
            ->with(compact('eligible', 'period_date'));
    }

    /**
     * Finds active assets that still have depreciable value left and have not
     * already been posted for the given period.
     */
    private function eligibleAssetsForPeriod($business_id, $period_date)
    {
        return AccountingFixedAsset::where('business_id', $business_id)
            ->where('status', 'active')
            ->where('depreciation_start_date', '<=', $period_date)
            ->whereDoesntHave('depreciations', function ($q) use ($period_date) {
                $q->where('period_date', $period_date);
            })
            ->get()
            ->filter(function ($asset) {
                return $asset->accumulated_depreciation < $asset->max_depreciable_amount;
            })
            ->values();
    }

    /**
     * Runs (posts) depreciation for every eligible asset for the chosen period.
     */
    public function runDepreciation(Request $request)
    {
        $business_id = $this->checkAccess('accounting.run_depreciation');

        $period_date = $this->util->uf_date($request->get('period_date'));
        $user_id = $request->session()->get('user.id');
        $accounting_settings = $this->accountingUtil->getAccountingSettings($business_id);

        $eligible = $this->eligibleAssetsForPeriod($business_id, $period_date);

        $posted = 0;
        $total_amount = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            foreach ($eligible as $asset) {
                try {
                    $result = $this->accountingUtil->postAssetDepreciation($asset, $period_date, $user_id, $accounting_settings);
                    if (! empty($result)) {
                        $posted++;
                        $total_amount += $result->amount;
                    }
                } catch (\Exception $e) {
                    $errors[] = $asset->name.': '.$e->getMessage();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return redirect()->back()->with('status', ['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }

        if ($posted === 0 && empty($errors)) {
            $msg = __('accounting::lang.no_assets_due_for_depreciation');
        } else {
            $msg = __('accounting::lang.depreciation_run_summary', ['count' => $posted, 'amount' => $this->util->num_f($total_amount, true)]);
            if (! empty($errors)) {
                $msg .= ' — '.implode('; ', $errors);
            }
        }

        return redirect()->action([\Modules\Accounting\Http\Controllers\FixedAssetController::class, 'index'])
            ->with('status', ['success' => 1, 'msg' => $msg]);
    }

    /**
     * Disposal modal form.
     */
    public function disposeForm($id)
    {
        $business_id = $this->checkAccess('accounting.dispose_fixed_asset');

        $asset = AccountingFixedAsset::where('business_id', $business_id)->findOrFail($id);

        if (request()->ajax()) {
            return view('accounting::fixed_asset.partials.dispose_form')->with(compact('asset'));
        }
    }

    /**
     * Posts the disposal journal and marks the asset disposed.
     */
    public function dispose(Request $request, $id)
    {
        $business_id = $this->checkAccess('accounting.dispose_fixed_asset');

        $request->validate([
            'disposal_date' => 'required',
            'disposal_type' => 'required|in:sold,written_off,damaged',
        ]);

        try {
            $asset = AccountingFixedAsset::where('business_id', $business_id)->findOrFail($id);

            if ($asset->status === 'disposed') {
                return ['success' => 0, 'msg' => __('accounting::lang.asset_already_disposed')];
            }

            DB::beginTransaction();

            $user_id = $request->session()->get('user.id');
            $accounting_settings = $this->accountingUtil->getAccountingSettings($business_id);

            $data = [
                'disposal_date' => $this->util->uf_date($request->get('disposal_date')),
                'disposal_type' => $request->get('disposal_type'),
                'disposal_amount' => $this->util->num_uf($request->get('disposal_amount', 0)),
                'disposal_notes' => $request->get('disposal_notes'),
                'deposit_to_account_id' => $request->get('deposit_to_account_id'),
                'gain_loss_account_id' => $request->get('gain_loss_account_id'),
            ];

            $this->accountingUtil->postAssetDisposal($asset, $data, $user_id, $accounting_settings);

            DB::commit();

            $output = ['success' => 1, 'msg' => __('accounting::lang.asset_disposed_success')];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => 0, 'msg' => $e->getMessage() ?: __('messages.something_went_wrong')];
        }

        return $output;
    }
}
