<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PEAR: kolom running average cost (AVCO) per varian per lokasi.
 * Pada database PEAR yang sudah berjalan kolom ini biasanya sudah ada,
 * jadi migration dibuat guarded supaya aman dijalankan ulang / di install baru.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('variation_location_details', 'avg_cost')) {
            Schema::table('variation_location_details', function (Blueprint $table) {
                $table->decimal('avg_cost', 22, 4)->default(0)->after('qty_available');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('variation_location_details', 'avg_cost')) {
            Schema::table('variation_location_details', function (Blueprint $table) {
                $table->dropColumn('avg_cost');
            });
        }
    }
};
