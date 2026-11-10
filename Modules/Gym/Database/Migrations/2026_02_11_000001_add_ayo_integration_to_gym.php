<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAyoIntegrationToGym extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Add ayo_booking_id to gym_bookings for linking
        if (Schema::hasTable('gym_bookings') && !Schema::hasColumn('gym_bookings', 'ayo_booking_id')) {
            Schema::table('gym_bookings', function (Blueprint $table) {
                $table->string('ayo_booking_id')->nullable()->after('booking_note');
                $table->string('ayo_order_detail_id')->nullable()->after('ayo_booking_id');
                $table->string('ayo_booking_source')->nullable()->after('ayo_order_detail_id');
                $table->string('ayo_status')->nullable()->after('ayo_booking_source');
                $table->index('ayo_booking_id');
            });
        }

        // Create ayo_booking_logs table for tracking sync
        if (!Schema::hasTable('ayo_booking_logs')) {
            Schema::create('ayo_booking_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->string('action'); // list_bookings, create_reservation, cancel_reservation, list_fields, notify
                $table->string('ayo_booking_id')->nullable();
                $table->text('request_payload')->nullable();
                $table->text('response_payload')->nullable();
                $table->integer('http_status_code')->nullable();
                $table->boolean('is_success')->default(false);
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index('business_id');
                $table->index('ayo_booking_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasTable('gym_bookings')) {
            Schema::table('gym_bookings', function (Blueprint $table) {
                $table->dropIndex(['ayo_booking_id']);
                $table->dropColumn(['ayo_booking_id', 'ayo_order_detail_id', 'ayo_booking_source', 'ayo_status']);
            });
        }

        Schema::dropIfExists('ayo_booking_logs');
    }
}
