<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class AccountingReconciliation extends Model
{
    protected $table = 'accounting_reconciliations';

    protected $fillable = [
        'business_id', 'account_id', 'period_date',
        'statement_balance', 'beginning_balance',
        'cleared_debit', 'cleared_credit', 'difference',
        'status', 'reconciled_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'period_date'        => 'date',
        'reconciled_at'      => 'datetime',
        'statement_balance'  => 'float',
        'beginning_balance'  => 'float',
        'cleared_debit'      => 'float',
        'cleared_credit'     => 'float',
        'difference'         => 'float',
    ];

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }

    public function lines()
    {
        return $this->hasMany(AccountingReconciliationLine::class, 'reconciliation_id');
    }

    public function clearedLines()
    {
        return $this->lines()->where('is_cleared', true);
    }
}
