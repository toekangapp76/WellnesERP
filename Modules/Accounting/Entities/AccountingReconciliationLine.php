<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class AccountingReconciliationLine extends Model
{
    protected $table = 'accounting_reconciliation_lines';

    protected $fillable = [
        'reconciliation_id', 'transaction_id', 'is_cleared',
    ];

    protected $casts = [
        'is_cleared' => 'boolean',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(AccountingReconciliation::class, 'reconciliation_id');
    }

    public function transaction()
    {
        return $this->belongsTo(AccountingAccountsTransaction::class, 'transaction_id');
    }
}
