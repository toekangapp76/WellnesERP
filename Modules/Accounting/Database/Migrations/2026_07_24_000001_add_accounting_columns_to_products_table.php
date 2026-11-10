<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountingColumnsToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Per-product override for the Accounting auto-mapping listener
            // (Modules\Accounting\Listeners\MapSellTransaction). Falls back to the
            // product's category (sales_account_id/purchase_account_id) and then to
            // the location's default accounting map when left empty.
            $table->unsignedBigInteger('revenue_account_id')->nullable()->after('warranty_id');
            $table->unsignedBigInteger('cogs_account_id')->nullable()->after('revenue_account_id');
            $table->unsignedBigInteger('inventory_account_id')->nullable()->after('cogs_account_id');
            $table->unsignedBigInteger('sales_discount_account_id')->nullable()->after('inventory_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['revenue_account_id', 'cogs_account_id', 'inventory_account_id', 'sales_discount_account_id']);
        });
    }
}
