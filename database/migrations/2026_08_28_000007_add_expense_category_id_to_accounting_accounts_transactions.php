<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PEAR: kaitkan baris jurnal (accounting_accounts_transactions) langsung ke
 * kategori pengeluaran. Ditulis oleh Modules/Accounting JournalEntryController
 * dan dibaca laporan "P&L Bisnis". Nullable & guarded.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('accounting_accounts_transactions', 'expense_category_id')) {
            Schema::table('accounting_accounts_transactions', function (Blueprint $table) {
                $table->unsignedInteger('expense_category_id')->nullable()->after('transaction_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('accounting_accounts_transactions', 'expense_category_id')) {
            Schema::table('accounting_accounts_transactions', function (Blueprint $table) {
                $table->dropColumn('expense_category_id');
            });
        }
    }
};
