<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PEAR: label pengelompokan kategori pengeluaran untuk laporan "P&L Bisnis"
 * (Modules/Accounting - ReportController::pnlBisnis). Nullable & guarded.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('expense_categories', 'pnl_group')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->string('pnl_group')->nullable()->after('code');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('expense_categories', 'pnl_group')) {
            Schema::table('expense_categories', function (Blueprint $table) {
                $table->dropColumn('pnl_group');
            });
        }
    }
};
