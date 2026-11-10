<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PEAR: catat mata uang transaksi (default 54 = IDR).
 * Guarded supaya aman dijalankan ulang / di install baru.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('transactions', 'currency_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->integer('currency_id')->default(54)->after('id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('transactions', 'currency_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('currency_id');
            });
        }
    }
};
