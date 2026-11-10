<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PEAR: kolom "Nomor Referensi Dokumen" untuk Purchase Requisition.
 * Pada database PEAR yang sudah berjalan kolom ini biasanya sudah ada,
 * jadi migration dibuat guarded supaya aman dijalankan ulang / di install baru.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('transactions', 'ref_no_doc')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('ref_no_doc')->nullable()->after('ref_no');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('transactions', 'ref_no_doc')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('ref_no_doc');
            });
        }
    }
};
