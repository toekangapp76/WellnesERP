<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAyoSyncColumns extends Migration
{
    public function up()
    {
        // Add ayo_field_id to gym_courts for mapping local courts to AYO fields
        if (Schema::hasTable('gym_courts') && !Schema::hasColumn('gym_courts', 'ayo_field_id')) {
            Schema::table('gym_courts', function (Blueprint $table) {
                $table->integer('ayo_field_id')->nullable()->after('sort_order');
                $table->index('ayo_field_id');
            });
        }

        // Add ayo_total_price and ayo_synced_at to gym_bookings
        if (Schema::hasTable('gym_bookings')) {
            Schema::table('gym_bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('gym_bookings', 'ayo_total_price')) {
                    $table->decimal('ayo_total_price', 22, 4)->nullable()->after('ayo_status');
                }
                if (!Schema::hasColumn('gym_bookings', 'ayo_synced_at')) {
                    $table->timestamp('ayo_synced_at')->nullable()->after('ayo_total_price');
                }
            });
        }

        // Add AYO accounting accounts in gym_settings (ayo_income_account_id, ayo_asset_account_id)
        // These are stored in the business gym_settings JSON, no migration needed for that.
    }

    public function down()
    {
        if (Schema::hasTable('gym_courts') && Schema::hasColumn('gym_courts', 'ayo_field_id')) {
            Schema::table('gym_courts', function (Blueprint $table) {
                $table->dropIndex(['ayo_field_id']);
                $table->dropColumn('ayo_field_id');
            });
        }

        if (Schema::hasTable('gym_bookings')) {
            Schema::table('gym_bookings', function (Blueprint $table) {
                if (Schema::hasColumn('gym_bookings', 'ayo_total_price')) {
                    $table->dropColumn('ayo_total_price');
                }
                if (Schema::hasColumn('gym_bookings', 'ayo_synced_at')) {
                    $table->dropColumn('ayo_synced_at');
                }
            });
        }
    }
}
