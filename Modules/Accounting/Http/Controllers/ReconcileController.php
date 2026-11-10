<?php

namespace Modules\Accounting\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Utils\ModuleUtil;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingReconciliation;
use Modules\Accounting\Entities\AccountingReconciliationLine;
use Modules\Accounting\Utils\AccountingUtil;

class ReconcileController extends Controller
{
    protected $accountingUtil;
    protected $moduleUtil;

    public function __construct(AccountingUtil $accountingUtil, ModuleUtil $moduleUtil)
    {
        $this->accountingUtil = $accountingUtil;
        $this->moduleUtil     = $moduleUtil;
    }

    /**
     * Daftar semua rekonsiliasi per akun bank.
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->_checkAccess($business_id)) {
            abort(403, 'Unauthorized action.');
        }

        $reconciliations = AccountingReconciliation::where('business_id', $business_id)
            ->with('account')
            ->orderBy('period_date', 'desc')
            ->get();

        $bank_accounts = AccountingAccount::where('business_id', $business_id)
            ->where('status', 'active')
            ->whereIn('account_primary_type', ['asset'])
            ->orderBy('name')
            ->get();

        return view('accounting::reconcile.index', compact('reconciliations', 'bank_accounts'));
    }

    /**
     * Form buat rekonsiliasi baru — pilih akun + periode.
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->_checkAccess($business_id)) {
            abort(403, 'Unauthorized action.');
        }

        $bank_accounts = AccountingAccount::where('business_id', $business_id)
            ->where('status', 'active')
            ->whereIn('account_primary_type', ['asset'])
            ->orderBy('name')
            ->get();

        return view('accounting::reconcile.create', compact('bank_accounts'));
    }

    /**
     * Simpan header rekonsiliasi dan redirect ke workspace edit.
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->_checkAccess($business_id)) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'account_id'        => 'required|integer',
            'period_date'       => 'required|date',
            'statement_balance' => 'required|numeric',
        ]);

        $account_id   = $request->input('account_id');
        $period_date  = Carbon::parse($request->input('period_date'))->endOfMonth()->toDateString();

        // Cek apakah sudah ada rekonsiliasi untuk periode yang sama
        $exists = AccountingReconciliation::where('business_id', $business_id)
            ->where('account_id', $account_id)
            ->where('period_date', $period_date)
            ->first();

        if ($exists) {
            return redirect()->route('accounting.reconcile.edit', $exists->id)
                ->with('status', ['success' => 1, 'msg' => __('accounting::lang.reconciliation_exists_redirected')]);
        }

        // Hitung saldo awal (balance dari semua transaksi sebelum awal bulan ini)
        $period_start = Carbon::parse($period_date)->startOfMonth()->toDateString();
        $beginning_balance = $this->_getAccountBalance($account_id, null, Carbon::parse($period_start)->subDay()->toDateString());

        try {
            DB::beginTransaction();

            $reconciliation = AccountingReconciliation::create([
                'business_id'       => $business_id,
                'account_id'        => $account_id,
                'period_date'       => $period_date,
                'statement_balance' => $this->accountingUtil->num_uf($request->input('statement_balance')),
                'beginning_balance' => $beginning_balance,
                'status'            => 'draft',
                'created_by'        => $request->session()->get('user.id'),
            ]);

            // Buat lines untuk semua transaksi pada periode ini
            $this->_createOrRefreshLines($reconciliation);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('Reconcile store: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('status', ['success' => 0, 'msg' => 'Gagal: ' . $e->getMessage()]);
        }

        return redirect()->route('accounting.reconcile.edit', $reconciliation->id);
    }

    /**
     * Workspace rekonsiliasi utama — tampilkan daftar transaksi + ceklis.
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->_checkAccess($business_id)) {
            abort(403, 'Unauthorized action.');
        }

        $reconciliation = AccountingReconciliation::where('business_id', $business_id)
            ->with(['account', 'lines'])
            ->findOrFail($id);

        // Refresh lines (tambahkan transaksi baru yang mungkin masuk sejak rekonsiliasi dibuat)
        if ($reconciliation->status === 'draft') {
            $this->_createOrRefreshLines($reconciliation);
            $reconciliation->refresh();
        }

        // Ambil semua transaksi periode ini beserta status cleared-nya
        $period_start = Carbon::parse($reconciliation->period_date)->startOfMonth()->toDateString();
        $period_end   = $reconciliation->period_date->toDateString();

        $cleared_ids = $reconciliation->lines()->where('is_cleared', true)->pluck('transaction_id')->toArray();

        $transactions = AccountingAccountsTransaction::where('accounting_account_id', $reconciliation->account_id)
            ->whereBetween('operation_date', [$period_start, $period_end])
            ->leftJoin('accounting_acc_trans_mappings as ATM', 'accounting_accounts_transactions.acc_trans_mapping_id', '=', 'ATM.id')
            ->leftJoin('transactions as T', 'accounting_accounts_transactions.transaction_id', '=', 'T.id')
            ->select(
                'accounting_accounts_transactions.id',
                'accounting_accounts_transactions.operation_date',
                'accounting_accounts_transactions.type',
                'accounting_accounts_transactions.amount',
                'accounting_accounts_transactions.sub_type',
                'ATM.ref_no as journal_ref',
                'T.invoice_no',
                'T.ref_no as trans_ref',
            )
            ->orderBy('accounting_accounts_transactions.operation_date', 'asc')
            ->orderBy('accounting_accounts_transactions.id', 'asc')
            ->get()
            ->map(function ($txn) use ($cleared_ids) {
                $txn->is_cleared = in_array($txn->id, $cleared_ids);
                return $txn;
            });

        // Hitung ringkasan
        $summary = $this->_calcSummary($reconciliation, $transactions);

        return view('accounting::reconcile.edit', compact('reconciliation', 'transactions', 'summary'));
    }

    /**
     * Simpan status cleared + statement balance, lalu hitung ulang.
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->_checkAccess($business_id)) {
            abort(403, 'Unauthorized action.');
        }

        $reconciliation = AccountingReconciliation::where('business_id', $business_id)
            ->findOrFail($id);

        if ($reconciliation->status === 'reconciled') {
            return response()->json(['success' => false, 'msg' => __('accounting::lang.reconciliation_already_reconciled')]);
        }

        $request->validate([
            'statement_balance' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            $cleared_ids        = $request->input('cleared_ids', []);
            $statement_balance  = $this->accountingUtil->num_uf($request->input('statement_balance'));
            $finalize           = $request->boolean('finalize');

            // Update semua lines
            AccountingReconciliationLine::where('reconciliation_id', $id)
                ->update(['is_cleared' => false]);

            if (!empty($cleared_ids)) {
                AccountingReconciliationLine::where('reconciliation_id', $id)
                    ->whereIn('transaction_id', $cleared_ids)
                    ->update(['is_cleared' => true]);
            }

            // Hitung ulang totals
            $period_start = Carbon::parse($reconciliation->period_date)->startOfMonth()->toDateString();
            $period_end   = $reconciliation->period_date->toDateString();

            $cleared_txns = AccountingAccountsTransaction::where('accounting_account_id', $reconciliation->account_id)
                ->whereIn('accounting_accounts_transactions.id', $cleared_ids)
                ->whereBetween('operation_date', [$period_start, $period_end])
                ->get();

            $cleared_debit  = $cleared_txns->where('type', 'debit')->sum('amount');
            $cleared_credit = $cleared_txns->where('type', 'credit')->sum('amount');

            // Saldo buku = saldo awal + cleared debit - cleared credit (untuk asset account)
            $book_balance = $reconciliation->beginning_balance + $cleared_debit - $cleared_credit;
            $difference   = $statement_balance - $book_balance;

            $update_data = [
                'statement_balance' => $statement_balance,
                'cleared_debit'     => $cleared_debit,
                'cleared_credit'    => $cleared_credit,
                'difference'        => $difference,
                'notes'             => $request->input('notes'),
            ];

            if ($finalize && abs($difference) < 0.01) {
                $update_data['status']         = 'reconciled';
                $update_data['reconciled_at']  = now();
            }

            $reconciliation->update($update_data);

            DB::commit();

            return response()->json([
                'success'         => true,
                'msg'             => $finalize && abs($difference) < 0.01
                    ? __('accounting::lang.reconciliation_completed')
                    : __('accounting::lang.reconciliation_saved'),
                'difference'      => $difference,
                'book_balance'    => $book_balance,
                'cleared_debit'   => $cleared_debit,
                'cleared_credit'  => $cleared_credit,
                'is_reconciled'   => $reconciliation->fresh()->status === 'reconciled',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('Reconcile update: ' . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus rekonsiliasi draft.
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->_checkAccess($business_id)) {
            abort(403, 'Unauthorized action.');
        }

        $reconciliation = AccountingReconciliation::where('business_id', $business_id)
            ->where('status', 'draft')
            ->findOrFail($id);

        $reconciliation->lines()->delete();
        $reconciliation->delete();

        return redirect()->route('accounting.reconcile.index')
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.deleted_success')]);
    }

    /**
     * Cetak laporan rekonsiliasi.
     */
    public function print($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!$this->_checkAccess($business_id)) {
            abort(403, 'Unauthorized action.');
        }

        $reconciliation = AccountingReconciliation::where('business_id', $business_id)
            ->with(['account', 'lines'])
            ->findOrFail($id);

        $period_start = Carbon::parse($reconciliation->period_date)->startOfMonth()->toDateString();
        $period_end   = $reconciliation->period_date->toDateString();

        $cleared_ids = $reconciliation->lines()->where('is_cleared', true)->pluck('transaction_id')->toArray();

        $all_transactions = AccountingAccountsTransaction::where('accounting_account_id', $reconciliation->account_id)
            ->whereBetween('operation_date', [$period_start, $period_end])
            ->leftJoin('accounting_acc_trans_mappings as ATM', 'accounting_accounts_transactions.acc_trans_mapping_id', '=', 'ATM.id')
            ->leftJoin('transactions as T', 'accounting_accounts_transactions.transaction_id', '=', 'T.id')
            ->select(
                'accounting_accounts_transactions.id',
                'accounting_accounts_transactions.operation_date',
                'accounting_accounts_transactions.type',
                'accounting_accounts_transactions.amount',
                'accounting_accounts_transactions.sub_type',
                'ATM.ref_no as journal_ref',
                'T.invoice_no',
                'T.ref_no as trans_ref',
            )
            ->orderBy('operation_date', 'asc')
            ->get()
            ->map(function ($txn) use ($cleared_ids) {
                $txn->is_cleared = in_array($txn->id, $cleared_ids);
                return $txn;
            });

        $cleared     = $all_transactions->where('is_cleared', true);
        $outstanding = $all_transactions->where('is_cleared', false);

        $summary = $this->_calcSummary($reconciliation, $all_transactions);

        $business = \App\Business::find($business_id);

        return view('accounting::reconcile.print', compact(
            'reconciliation', 'all_transactions', 'cleared', 'outstanding', 'summary', 'business'
        ));
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function _checkAccess($business_id): bool
    {
        return auth()->user()->can('superadmin') ||
            ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')
                && auth()->user()->can('accounting.manage_accounts'));
    }

    private function _getAccountBalance($account_id, $from = null, $to = null): float
    {
        $query = AccountingAccountsTransaction::where('accounting_account_id', $account_id)
            ->join('accounting_accounts as AA', 'accounting_accounts_transactions.accounting_account_id', '=', 'AA.id');

        if ($from) {
            $query->whereDate('operation_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('operation_date', '<=', $to);
        }

        $balance_formula = $this->accountingUtil->balanceFormula('AA', 'accounting_accounts_transactions');
        $result = $query->selectRaw($balance_formula)->first();

        return $result ? (float) $result->balance : 0.0;
    }

    private function _createOrRefreshLines(AccountingReconciliation $reconciliation): void
    {
        $period_start = Carbon::parse($reconciliation->period_date)->startOfMonth()->toDateString();
        $period_end   = $reconciliation->period_date->toDateString();

        $existing_ids = $reconciliation->lines()->pluck('transaction_id')->toArray();

        $new_txns = AccountingAccountsTransaction::where('accounting_account_id', $reconciliation->account_id)
            ->whereBetween('operation_date', [$period_start, $period_end])
            ->whereNotIn('id', $existing_ids)
            ->pluck('id');

        foreach ($new_txns as $txn_id) {
            AccountingReconciliationLine::create([
                'reconciliation_id' => $reconciliation->id,
                'transaction_id'    => $txn_id,
                'is_cleared'        => false,
            ]);
        }
    }

    private function _calcSummary(AccountingReconciliation $reconciliation, $transactions): array
    {
        $cleared     = $transactions->where('is_cleared', true);
        $outstanding = $transactions->where('is_cleared', false);

        $cleared_debit   = $cleared->where('type', 'debit')->sum('amount');
        $cleared_credit  = $cleared->where('type', 'credit')->sum('amount');

        // Untuk akun aset: saldo = saldo_awal + debit - kredit
        $book_balance = $reconciliation->beginning_balance + $cleared_debit - $cleared_credit;
        $difference   = $reconciliation->statement_balance - $book_balance;

        $outstanding_debit  = $outstanding->where('type', 'debit')->sum('amount');
        $outstanding_credit = $outstanding->where('type', 'credit')->sum('amount');

        return [
            'book_balance'       => $book_balance,
            'difference'         => $difference,
            'is_balanced'        => abs($difference) < 0.01,
            'cleared_count'      => $cleared->count(),
            'outstanding_count'  => $outstanding->count(),
            'cleared_debit'      => $cleared_debit,
            'cleared_credit'     => $cleared_credit,
            'outstanding_debit'  => $outstanding_debit,
            'outstanding_credit' => $outstanding_credit,
        ];
    }
}
