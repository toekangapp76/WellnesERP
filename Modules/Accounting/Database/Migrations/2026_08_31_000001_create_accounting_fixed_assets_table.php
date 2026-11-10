<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingFixedAssetsTable extends Migration
{
    public function up()
    {
        Schema::create('accounting_fixed_assets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();          // App\BusinessLocation, lokasi fisik aset
            $table->string('asset_code');                                // kode aset, auto-generate jika kosong
            $table->string('name');
            $table->string('category')->nullable();                     // mis. "Kendaraan", "Peralatan Kantor"
            $table->text('description')->nullable();

            $table->date('purchase_date');
            $table->decimal('purchase_cost', 22, 4)->default(0);         // harga perolehan
            $table->decimal('salvage_value', 22, 4)->default(0);         // nilai residu di akhir umur ekonomis
            $table->unsignedInteger('useful_life_months');               // umur ekonomis dalam bulan
            $table->string('depreciation_method')->default('straight_line');
            $table->date('depreciation_start_date');                     // biasanya = purchase_date

            // Akun COA terkait. Jika kosong, posting penyusutan memakai akun
            // default dari accounting_settings milik business.
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->unsignedBigInteger('depreciation_expense_account_id')->nullable();
            $table->unsignedBigInteger('accumulated_depreciation_account_id')->nullable();

            // Cache akumulasi penyusutan yang sudah diposting (sumber kebenaran
            // tetap accounting_fixed_asset_depreciations, kolom ini untuk tampilan cepat).
            $table->decimal('accumulated_depreciation', 22, 4)->default(0);

            $table->enum('status', ['active', 'fully_depreciated', 'disposed'])->default('active');

            $table->date('disposed_at')->nullable();
            $table->enum('disposal_type', ['sold', 'written_off', 'damaged'])->nullable();
            $table->decimal('disposal_amount', 22, 4)->nullable();
            $table->text('disposal_notes')->nullable();
            $table->unsignedBigInteger('disposal_acc_trans_mapping_id')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->index(['business_id', 'status'], 'acc_fixed_assets_biz_status_idx');
            $table->unique(['business_id', 'asset_code'], 'acc_fixed_assets_biz_code_uniq');
        });

        Schema::create('accounting_fixed_asset_depreciations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('fixed_asset_id');
            $table->unsignedInteger('business_id');
            $table->date('period_date');                                 // akhir periode penyusutan (mis. 2026-08-31)
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('accumulated_after', 22, 4)->default(0);      // snapshot akumulasi setelah baris ini
            $table->unsignedBigInteger('acc_trans_mapping_id')->nullable(); // jurnal yang diposting untuk baris ini
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->unique(['fixed_asset_id', 'period_date'], 'acc_fixed_asset_depr_uniq');
            $table->index('business_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounting_fixed_asset_depreciations');
        Schema::dropIfExists('accounting_fixed_assets');
    }
}
