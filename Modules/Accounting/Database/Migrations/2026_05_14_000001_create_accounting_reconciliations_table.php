<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountingReconciliationsTable extends Migration
{
    public function up()
    {
        Schema::create('accounting_reconciliations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('account_id');
            $table->date('period_date');                                     // tanggal akhir periode rekonsiliasi
            $table->decimal('statement_balance', 22, 4)->default(0);        // saldo rekening koran
            $table->decimal('beginning_balance', 22, 4)->default(0);        // saldo buku awal periode
            $table->decimal('cleared_debit', 22, 4)->default(0);            // total debit yang sudah clear
            $table->decimal('cleared_credit', 22, 4)->default(0);           // total kredit yang sudah clear
            $table->decimal('difference', 22, 4)->default(0);               // selisih (harus 0)
            $table->enum('status', ['draft', 'reconciled'])->default('draft');
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->index(['business_id', 'account_id', 'period_date'], 'acc_recon_biz_acc_period_idx');
        });

        Schema::create('accounting_reconciliation_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('reconciliation_id');
            $table->unsignedBigInteger('transaction_id');   // FK ke accounting_accounts_transactions.id
            $table->boolean('is_cleared')->default(false);
            $table->timestamps();

            $table->unique(['reconciliation_id', 'transaction_id'], 'acc_recon_lines_uniq');
            $table->index('reconciliation_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounting_reconciliation_lines');
        Schema::dropIfExists('accounting_reconciliations');
    }
}
