<?php

namespace Modules\Accounting\Utils;

use App\Business;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\Util;
use DB;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingAccTransMapping;
use Modules\Accounting\Entities\AccountingFixedAssetDepreciation;

class AccountingUtil extends Util
{
    public function balanceFormula($accounting_accounts_alias = 'accounting_accounts',
                                 $accounting_account_transaction_alias = 'AAT')
    {
        return "SUM( IF(
            ($accounting_accounts_alias.account_primary_type='asset' AND $accounting_account_transaction_alias.type='debit')
            OR ($accounting_accounts_alias.account_primary_type='expenses' AND $accounting_account_transaction_alias.type='debit')
            OR ($accounting_accounts_alias.account_primary_type='income' AND $accounting_account_transaction_alias.type='credit')
            OR ($accounting_accounts_alias.account_primary_type='equity' AND $accounting_account_transaction_alias.type='credit')
            OR ($accounting_accounts_alias.account_primary_type='liability' AND $accounting_account_transaction_alias.type='credit'), 
            amount, -1*amount)) as balance";
    }

    public function getAccountingSettings($business_id)
    {
        $accounting_settings = Business::where('id', $business_id)
                                ->value('accounting_settings');

        $accounting_settings = ! empty($accounting_settings) ? json_decode($accounting_settings, true) : [];

        return $accounting_settings;
    }

    public function getAgeingReport($business_id, $type, $group_by, $location_id = null)
    {
        $today = \Carbon::now()->format('Y-m-d');
        $query = Transaction::where('transactions.business_id', $business_id);

        if ($type == 'sell') {
            $query->where('transactions.type', 'sell')
            ->where('transactions.status', 'final');
        } elseif ($type == 'purchase') {
            $query->where('transactions.type', 'purchase')
                ->where('transactions.status', 'received');
        }

        if (! empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        $dues = $query->whereNotNull('transactions.pay_term_number')
                ->whereIn('transactions.payment_status', ['partial', 'due'])
                ->join('contacts as c', 'c.id', '=', 'transactions.contact_id')
                ->select(
                    DB::raw(
                        'DATEDIFF(
                            "'.$today.'", 
                            IF(
                                transactions.pay_term_type="days",
                                DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY),
                                DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH)
                            )
                        ) as diff'
                    ),
                    DB::raw('SUM(transactions.final_total - 
                        (SELECT COALESCE(SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)), 0) 
                        FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) )  
                        as total_due'),

                    'c.name as contact_name',
                    'transactions.contact_id',
                    'transactions.invoice_no',
                    'transactions.ref_no',
                    'transactions.transaction_date',
                    DB::raw('IF(
                        transactions.pay_term_type="days",
                        DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY),
                        DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH)
                    ) as due_date')
                )
                ->groupBy('transactions.id')
                ->get();

        $report_details = [];
        if ($group_by == 'contact') {
            foreach ($dues as $due) {
                if (! isset($report_details[$due->contact_id])) {
                    $report_details[$due->contact_id] = [
                        'name' => $due->contact_name,
                        '<1' => 0,
                        '1_30' => 0,
                        '31_60' => 0,
                        '61_90' => 0,
                        '>90' => 0,
                        'total_due' => 0,
                    ];
                }

                if ($due->diff < 1) {
                    $report_details[$due->contact_id]['<1'] += $due->total_due;
                } elseif ($due->diff >= 1 && $due->diff <= 30) {
                    $report_details[$due->contact_id]['1_30'] += $due->total_due;
                } elseif ($due->diff >= 31 && $due->diff <= 60) {
                    $report_details[$due->contact_id]['31_60'] += $due->total_due;
                } elseif ($due->diff >= 61 && $due->diff <= 90) {
                    $report_details[$due->contact_id]['61_90'] += $due->total_due;
                } elseif ($due->diff > 90) {
                    $report_details[$due->contact_id]['>90'] += $due->total_due;
                }

                $report_details[$due->contact_id]['total_due'] += $due->total_due;
            }
        } elseif ($group_by == 'due_date') {
            $report_details = [
                'current' => [],
                '1_30' => [],
                '31_60' => [],
                '61_90' => [],
                '>90' => [],
            ];
            foreach ($dues as $due) {
                $temp_array = [
                    'transaction_date' => $this->format_date($due->transaction_date),
                    'due_date' => $this->format_date($due->due_date),
                    'ref_no' => $due->ref_no,
                    'invoice_no' => $due->invoice_no,
                    'contact_name' => $due->contact_name,
                    'due' => $due->total_due,
                ];
                if ($due->diff < 1) {
                    $report_details['current'][] = $temp_array;
                } elseif ($due->diff >= 1 && $due->diff <= 30) {
                    $report_details['1_30'][] = $temp_array;
                } elseif ($due->diff >= 31 && $due->diff <= 60) {
                    $report_details['31_60'][] = $temp_array;
                } elseif ($due->diff >= 61 && $due->diff <= 90) {
                    $report_details['61_90'][] = $temp_array;
                } elseif ($due->diff > 90) {
                    $report_details['>90'][] = $temp_array;
                }
            }
        }

        return $report_details;
    }

    /**
     * Function to delete a mapping
     */
    public function deleteMap($transaction_id, $transaction_payment_id){
        AccountingAccountsTransaction::where('transaction_id', $transaction_id)
            ->whereIn('map_type', ['payment_account', 'deposit_to', 'ppn_account', 'discount_account', 'cogs_account', 'inventory_cogs_account'])
            ->where('transaction_payment_id', $transaction_payment_id)
            ->delete();
    }

    /**
     * Posts a sell transaction's journal entries broken down per resolved account: a single
     * payment_account debit for the full amount collected, then one deposit_to (revenue) credit
     * line, one discount_account debit line, and one cogs_account/inventory_cogs_account
     * debit/credit pair per distinct account used across the sale's lines (products/categories
     * can each map to their own accounts; see Product::resolvedRevenueAccountId() and friends).
     *
     * Deletes any previously posted rows for this transaction first so re-runs (e.g. editing a
     * sale, or voiding it) stay idempotent instead of accumulating duplicate journal lines.
     */
    public function saveSellDetailedMap($id, $user_id, $business_id, $operation_date, $payment_account, $final_total, array $revenue_by_account, array $discount_by_account, array $cogs_by_account, array $inventory_by_account)
    {
        $transaction = Transaction::where('business_id', $business_id)->where('id', $id)->firstOrFail();
        $gl_date = $operation_date ?? $transaction->transaction_date ?? \Carbon::now();

        AccountingAccountsTransaction::where('transaction_id', $id)
            ->whereNull('transaction_payment_id')
            ->whereIn('map_type', ['payment_account', 'deposit_to', 'discount_account', 'cogs_account', 'inventory_cogs_account'])
            ->delete();

        $base_row = [
            'transaction_id' => $id,
            'transaction_payment_id' => null,
            'sub_type' => 'sell',
            'created_by' => $user_id,
            'operation_date' => $gl_date,
        ];

        AccountingAccountsTransaction::createTransaction(array_merge($base_row, [
            'accounting_account_id' => $payment_account,
            'amount' => round($final_total, 4),
            'type' => 'debit',
            'map_type' => 'payment_account',
        ]));

        $this->createDetailedLines($base_row, $revenue_by_account, 'credit', 'deposit_to');
        $this->createDetailedLines($base_row, $discount_by_account, 'debit', 'discount_account');
        $this->createDetailedLines($base_row, $cogs_by_account, 'debit', 'cogs_account');
        $this->createDetailedLines($base_row, $inventory_by_account, 'credit', 'inventory_cogs_account');
    }

    private function createDetailedLines(array $base_row, array $amounts_by_account, $type, $map_type)
    {
        foreach ($amounts_by_account as $account_id => $amount) {
            $amount = round($amount, 4);
            if (empty($account_id) || $amount <= 0) {
                continue;
            }

            AccountingAccountsTransaction::createTransaction(array_merge($base_row, [
                'accounting_account_id' => $account_id,
                'amount' => $amount,
                'type' => $type,
                'map_type' => $map_type,
            ]));
        }
    }

    /**
     * Function to save a mapping
     */
    public function saveMap($type, $id, $user_id, $business_id, $deposit_to, $payment_account, $ppn_account = null, $discount_account = null, $operation_date = null, $cogs_account = null, $inventory_cogs_account = null, $cogs_amount = null){
        if ($type == 'sell') {
            $transaction = Transaction::where('business_id', $business_id)->where('id', $id)->firstorFail();

            $gl_date = $operation_date ?? $transaction->transaction_date ?? \Carbon::now();

            // A manual remap collapses any per-product/category detailed revenue+discount rows
            // (see saveSellDetailedMap()) back into the single account chosen here.
            AccountingAccountsTransaction::where('transaction_id', $id)
                ->whereNull('transaction_payment_id')
                ->whereIn('map_type', ['deposit_to', 'discount_account'])
                ->delete();

            // Payment Account (Bank/Cash) = Debit (aset masuk)
            $payment_data = [
                'accounting_account_id' => $payment_account,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'debit',
                'sub_type' => $type,
                'map_type' => 'payment_account',
                'created_by' => $user_id,
                'operation_date' => $gl_date,
            ];

            // Deposit To (Revenue/Sales) = Credit (pendapatan masuk)
            $deposit_data = [
                'accounting_account_id' => $deposit_to,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'credit',
                'sub_type' => $type,
                'map_type' => 'deposit_to',
                'created_by' => $user_id,
                'operation_date' => $gl_date,
            ];
        } elseif (in_array($type, ['purchase_payment', 'sell_payment'])) {
            $transaction_payment = TransactionPayment::where('id', $id)->where('business_id', $business_id)
                            ->firstorFail();
            
            $gl_date = $operation_date ?? $transaction_payment->paid_on ?? \Carbon::now();

            // Payment Account (Bank/Cash) = Debit (kas masuk)
            $payment_data = [
                'accounting_account_id' => $payment_account,
                'transaction_id' => null,
                'transaction_payment_id' => $id,
                'amount' => $transaction_payment->amount,
                'type' => 'debit',
                'sub_type' => $type,
                'map_type' => 'payment_account',
                'created_by' => $user_id,
                'operation_date' => $gl_date,
            ];

            // Deposit To (A/R atau Revenue) = Credit
            $deposit_data = [
                'accounting_account_id' => $deposit_to,
                'transaction_id' => null,
                'transaction_payment_id' => $id,
                'amount' => $transaction_payment->amount,
                'type' => 'credit',
                'sub_type' => $type,
                'map_type' => 'deposit_to',
                'created_by' => $user_id,
                'operation_date' => $gl_date,
            ];
        } elseif ($type == 'purchase') {
            $transaction = Transaction::where('business_id', $business_id)->where('id', $id)->firstorFail();
            
           
            $gl_date = $operation_date ?? $transaction->transaction_date ?? \Carbon::now();
            
            // Calculate amounts
            $tax_amount = $transaction->tax_amount ?? 0;
            $discount_amount = $transaction->discount_amount ?? 0;
            $base_purchase = $transaction->final_total - $tax_amount + $discount_amount;
            
            // Purchase/Inventory (debit) - pembelian barang
            $payment_data = [
                'accounting_account_id' => $payment_account,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $base_purchase,
                'type' => 'debit',
                'sub_type' => $type,
                'map_type' => 'payment_account',
                'created_by' => $user_id,
                'operation_date' => $gl_date,
            ];

            // Accounts Payable (credit) - hutang ke supplier
            $deposit_data = [
                'accounting_account_id' => $deposit_to,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'credit',
                'sub_type' => $type,
                'map_type' => 'deposit_to',
                'created_by' => $user_id,
                'operation_date' => $gl_date,
            ];
            
            // Tax (debit) - pajak yang bisa diklaim
            if (!empty($ppn_account) && $tax_amount > 0) {
                $ppn_data = [
                    'accounting_account_id' => $ppn_account,
                    'transaction_id' => $id,
                    'transaction_payment_id' => null,
                    'amount' => $tax_amount,
                    'type' => 'debit',
                    'sub_type' => $type,
                    'map_type' => 'ppn_account',
                    'created_by' => $user_id,
                    'operation_date' => $gl_date,
                ];
                AccountingAccountsTransaction::updateOrCreateMapTransaction($ppn_data);
            }
            
            // Discount Received (credit) - diskon yang diterima
            if (!empty($discount_account) && $discount_amount > 0) {
                $discount_data = [
                    'accounting_account_id' => $discount_account,
                    'transaction_id' => $id,
                    'transaction_payment_id' => null,
                    'amount' => $discount_amount,
                    'type' => 'credit',
                    'sub_type' => $type,
                    'map_type' => 'discount_account',
                    'created_by' => $user_id,
                    'operation_date' => $gl_date,
                ];
                AccountingAccountsTransaction::updateOrCreateMapTransaction($discount_data);
            }        }elseif ($type == 'expense') {
            $transaction = Transaction::where('business_id', $business_id)->where('id', $id)->firstorFail();
            
            $gl_date = $operation_date ?? $transaction->transaction_date ?? \Carbon::now();
            
            $payment_data = [
                'accounting_account_id' => $payment_account,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'credit',
                'sub_type' => $type,
                'map_type' => 'payment_account',
                'created_by' => $user_id,
                'operation_date' => $gl_date,
            ];

            $deposit_data = [
                'accounting_account_id' => $deposit_to,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'debit',
                'sub_type' => $type,
                'map_type' => 'deposit_to',
                'created_by' => $user_id,
                'operation_date' => $gl_date,
            ];
        }

        if ($type == 'expense') {
            AccountingAccountsTransaction::where('transaction_id', $id)
                ->whereNull('transaction_payment_id')
                ->whereIn('map_type', ['payment_account', 'deposit_to'])
                ->delete();
            AccountingAccountsTransaction::createTransaction($payment_data);
            AccountingAccountsTransaction::createTransaction($deposit_data);
        } else {
            AccountingAccountsTransaction::updateOrCreateMapTransaction($payment_data);
            AccountingAccountsTransaction::updateOrCreateMapTransaction($deposit_data);

            // COGS entry for sell: Debit COGS, Credit Inventory
            if ($type == 'sell' && !empty($cogs_account) && !empty($inventory_cogs_account) && $cogs_amount > 0) {
                AccountingAccountsTransaction::updateOrCreateMapTransaction([
                    'accounting_account_id'  => $cogs_account,
                    'transaction_id'         => $id,
                    'transaction_payment_id' => null,
                    'amount'                 => $cogs_amount,
                    'type'                   => 'debit',
                    'sub_type'               => 'sell',
                    'map_type'               => 'cogs_account',
                    'created_by'             => $user_id,
                    'operation_date'         => $gl_date,
                ]);

                AccountingAccountsTransaction::updateOrCreateMapTransaction([
                    'accounting_account_id'  => $inventory_cogs_account,
                    'transaction_id'         => $id,
                    'transaction_payment_id' => null,
                    'amount'                 => $cogs_amount,
                    'type'                   => 'credit',
                    'sub_type'               => 'sell',
                    'map_type'               => 'inventory_cogs_account',
                    'created_by'             => $user_id,
                    'operation_date'         => $gl_date,
                ]);
            }
        }
    }

    /**
     * Resolves the GL account to use for one of the fixed-asset roles
     * (depreciation expense / accumulated depreciation), preferring the
     * per-asset override and falling back to the business-wide default
     * configured in Accounting > Settings > Aset Tetap.
     */
    public function resolveFixedAssetAccount($asset, $role, $accounting_settings)
    {
        $override_field = $role.'_account_id';
        if (! empty($asset->{$override_field})) {
            return $asset->{$override_field};
        }

        $default_key = 'default_'.$role.'_account_id';

        return $accounting_settings[$default_key] ?? null;
    }

    /**
     * Posts one period's straight-line depreciation for a single asset:
     * Debit Depreciation Expense, Credit Accumulated Depreciation.
     *
     * Creates the accounting_fixed_asset_depreciations row that both records
     * the history and (via its unique constraint on asset+period) stops the
     * same period from ever being posted twice for the same asset.
     *
     * @return \Modules\Accounting\Entities\AccountingFixedAssetDepreciation
     */
    public function postAssetDepreciation($asset, $period_date, $user_id, $accounting_settings = null)
    {
        $business_id = $asset->business_id;
        $accounting_settings = $accounting_settings ?? $this->getAccountingSettings($business_id);

        $remaining = round($asset->max_depreciable_amount - $asset->accumulated_depreciation, 4);
        $amount = min($asset->monthlyDepreciationAmount(), max(0, $remaining));

        if ($amount <= 0) {
            return null;
        }

        $expense_account_id = $this->resolveFixedAssetAccount($asset, 'depreciation_expense', $accounting_settings);
        $accumulated_account_id = $this->resolveFixedAssetAccount($asset, 'accumulated_depreciation', $accounting_settings);

        if (empty($expense_account_id) || empty($accumulated_account_id)) {
            throw new \Exception(__('accounting::lang.fixed_asset_accounts_not_configured'));
        }

        $ref_count = $this->setAndGetReferenceCount('asset_depreciation', $business_id);
        $prefix = ! empty($accounting_settings['depreciation_prefix']) ? $accounting_settings['depreciation_prefix'] : '';
        $ref_no = $this->generateReferenceNumber('asset_depreciation', $ref_count, $business_id, $prefix);

        $mapping = new AccountingAccTransMapping();
        $mapping->business_id = $business_id;
        $mapping->ref_no = $ref_no;
        $mapping->note = __('accounting::lang.depreciation_journal_note', ['asset' => $asset->name, 'code' => $asset->asset_code]);
        $mapping->type = 'asset_depreciation';
        $mapping->created_by = $user_id;
        $mapping->operation_date = $period_date;
        $mapping->save();

        AccountingAccountsTransaction::create([
            'acc_trans_mapping_id' => $mapping->id,
            'accounting_account_id' => $expense_account_id,
            'amount' => $amount,
            'type' => 'debit',
            'sub_type' => 'asset_depreciation',
            'created_by' => $user_id,
            'operation_date' => $period_date,
        ]);

        AccountingAccountsTransaction::create([
            'acc_trans_mapping_id' => $mapping->id,
            'accounting_account_id' => $accumulated_account_id,
            'amount' => $amount,
            'type' => 'credit',
            'sub_type' => 'asset_depreciation',
            'created_by' => $user_id,
            'operation_date' => $period_date,
        ]);

        $asset->accumulated_depreciation = round($asset->accumulated_depreciation + $amount, 4);
        if ($asset->accumulated_depreciation >= $asset->max_depreciable_amount) {
            $asset->status = 'fully_depreciated';
        }
        $asset->save();

        return AccountingFixedAssetDepreciation::create([
            'fixed_asset_id' => $asset->id,
            'business_id' => $business_id,
            'period_date' => $period_date,
            'amount' => $amount,
            'accumulated_after' => $asset->accumulated_depreciation,
            'acc_trans_mapping_id' => $mapping->id,
            'created_by' => $user_id,
        ]);
    }

    /**
     * Posts the disposal journal for an asset: removes its cost and
     * accumulated depreciation from the books, records any sale proceeds,
     * and posts the resulting gain or loss on disposal as the balancing line.
     *
     *   Debit  Cash/Bank (deposit_to)        = disposal_amount   (only if > 0)
     *   Debit  Accumulated Depreciation      = accumulated_depreciation
     *   Debit  Loss on Disposal              = max(0, book_value - disposal_amount)
     *   Credit Fixed Asset                   = purchase_cost
     *   Credit Gain on Disposal              = max(0, disposal_amount - book_value)
     */
    public function postAssetDisposal($asset, array $data, $user_id, $accounting_settings = null)
    {
        $business_id = $asset->business_id;
        $accounting_settings = $accounting_settings ?? $this->getAccountingSettings($business_id);

        $asset_account_id = $asset->asset_account_id;
        $accumulated_account_id = $this->resolveFixedAssetAccount($asset, 'accumulated_depreciation', $accounting_settings);
        $gain_loss_account_id = ! empty($data['gain_loss_account_id']) ? $data['gain_loss_account_id']
            : ($accounting_settings['default_disposal_gain_loss_account_id'] ?? null);

        if (empty($asset_account_id) || empty($accumulated_account_id) || empty($gain_loss_account_id)) {
            throw new \Exception(__('accounting::lang.fixed_asset_disposal_accounts_not_configured'));
        }

        $disposal_amount = round((float) ($data['disposal_amount'] ?? 0), 4);
        $book_value = round($asset->book_value, 4);
        $gain = round($disposal_amount - $book_value, 4);

        $ref_count = $this->setAndGetReferenceCount('asset_disposal', $business_id);
        $prefix = ! empty($accounting_settings['fixed_asset_prefix']) ? $accounting_settings['fixed_asset_prefix'] : '';
        $ref_no = $this->generateReferenceNumber('asset_disposal', $ref_count, $business_id, $prefix);

        $mapping = new AccountingAccTransMapping();
        $mapping->business_id = $business_id;
        $mapping->ref_no = $ref_no;
        $mapping->note = __('accounting::lang.disposal_journal_note', ['asset' => $asset->name, 'code' => $asset->asset_code]);
        $mapping->type = 'asset_disposal';
        $mapping->created_by = $user_id;
        $mapping->operation_date = $data['disposal_date'];
        $mapping->save();

        $line = function ($account_id, $type, $amount) use ($mapping, $user_id, $data) {
            if ($amount <= 0) {
                return;
            }
            AccountingAccountsTransaction::create([
                'acc_trans_mapping_id' => $mapping->id,
                'accounting_account_id' => $account_id,
                'amount' => round($amount, 4),
                'type' => $type,
                'sub_type' => 'asset_disposal',
                'created_by' => $user_id,
                'operation_date' => $data['disposal_date'],
            ]);
        };

        if ($disposal_amount > 0 && ! empty($data['deposit_to_account_id'])) {
            $line($data['deposit_to_account_id'], 'debit', $disposal_amount);
        }
        $line($accumulated_account_id, 'debit', $asset->accumulated_depreciation);
        $line($gain_loss_account_id, 'debit', max(0, -$gain));
        $line($asset_account_id, 'credit', $asset->purchase_cost);
        $line($gain_loss_account_id, 'credit', max(0, $gain));

        $asset->status = 'disposed';
        $asset->disposed_at = $data['disposal_date'];
        $asset->disposal_type = $data['disposal_type'];
        $asset->disposal_amount = $disposal_amount;
        $asset->disposal_notes = $data['disposal_notes'] ?? null;
        $asset->disposal_acc_trans_mapping_id = $mapping->id;
        $asset->save();

        return $mapping;
    }
}
