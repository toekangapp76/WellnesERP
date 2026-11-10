<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class AccountingFixedAsset extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'purchase_date' => 'date',
        'depreciation_start_date' => 'date',
        'disposed_at' => 'date',
    ];

    public function asset_account()
    {
        return $this->belongsTo(AccountingAccount::class, 'asset_account_id');
    }

    public function depreciation_expense_account()
    {
        return $this->belongsTo(AccountingAccount::class, 'depreciation_expense_account_id');
    }

    public function accumulated_depreciation_account()
    {
        return $this->belongsTo(AccountingAccount::class, 'accumulated_depreciation_account_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function depreciations()
    {
        return $this->hasMany(AccountingFixedAssetDepreciation::class, 'fixed_asset_id')
            ->orderBy('period_date');
    }

    /**
     * Total penyusutan maksimum yang boleh diakumulasikan (harga perolehan - nilai residu).
     */
    public function getMaxDepreciableAmountAttribute()
    {
        return max(0, $this->purchase_cost - $this->salvage_value);
    }

    /**
     * Nilai buku saat ini (harga perolehan - akumulasi penyusutan berjalan).
     */
    public function getBookValueAttribute()
    {
        return max($this->salvage_value, $this->purchase_cost - $this->accumulated_depreciation);
    }

    /**
     * Besaran penyusutan bulanan berdasarkan metode garis lurus.
     * Metode lain dapat ditambahkan di sini nanti.
     */
    public function monthlyDepreciationAmount()
    {
        if ($this->useful_life_months <= 0) {
            return 0;
        }

        if ($this->depreciation_method === 'straight_line') {
            return round($this->max_depreciable_amount / $this->useful_life_months, 4);
        }

        return 0;
    }

    /**
     * Apakah aset ini sudah pernah memiliki penyusutan yang diposting.
     * Dipakai untuk mengunci field finansial saat edit agar riwayat jurnal tidak menyimpang.
     */
    public function hasPostedDepreciation()
    {
        return $this->depreciations()->exists();
    }
}
