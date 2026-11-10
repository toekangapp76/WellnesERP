<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PEAR: landed cost per unit (base unit) untuk tiap baris pembelian.
 * Nilai = alokasi proporsional dari additional expenses (freight/duty/handling)
 * pada Purchase, dibagi berdasarkan nilai subtotal baris.
 * Guarded supaya aman dijalankan ulang / di install baru.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('purchase_lines', 'landed_cost_per_unit')) {
            Schema::table('purchase_lines', function (Blueprint $table) {
                $table->decimal('landed_cost_per_unit', 22, 4)->default(0)->after('purchase_price_inc_tax');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('purchase_lines', 'landed_cost_per_unit')) {
            Schema::table('purchase_lines', function (Blueprint $table) {
                $table->dropColumn('landed_cost_per_unit');
            });
        }
    }
};
