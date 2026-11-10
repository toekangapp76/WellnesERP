<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class AccountingFixedAssetDepreciation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'period_date' => 'date',
    ];

    public function fixed_asset()
    {
        return $this->belongsTo(AccountingFixedAsset::class, 'fixed_asset_id');
    }

    public function mapping()
    {
        return $this->belongsTo(AccountingAccTransMapping::class, 'acc_trans_mapping_id');
    }
}
