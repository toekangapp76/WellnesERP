<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGymBookingFeatures extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add days_added column to gym_hour_topups table
        if (Schema::hasTable('gym_hour_topups') && !Schema::hasColumn('gym_hour_topups', 'days_added')) {
            Schema::table('gym_hour_topups', function (Blueprint $table) {
                $table->integer('days_added')->default(0)->after('hours_added');
            });
        }

        // Add OTP columns to gym_attendances for check-in verification
        if (Schema::hasTable('gym_attendances') && !Schema::hasColumn('gym_attendances', 'otp_code')) {
            Schema::table('gym_attendances', function (Blueprint $table) {
                $table->string('otp_code', 6)->nullable()->after('duration_minutes');
                $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
                $table->boolean('otp_verified')->default(false)->after('otp_expires_at');
            });
        }

        // Add renewed_from_id to transactions for tracking renewal
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'renewed_from_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $column = $table->unsignedBigInteger('renewed_from_id')->nullable();

                //On a fresh install gym_priority is added by a later migration
                if (Schema::hasColumn('transactions', 'gym_priority')) {
                    $column->after('gym_priority');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('gym_hour_topups') && Schema::hasColumn('gym_hour_topups', 'days_added')) {
            Schema::table('gym_hour_topups', function (Blueprint $table) {
                $table->dropColumn('days_added');
            });
        }

        if (Schema::hasTable('gym_attendances')) {
            Schema::table('gym_attendances', function (Blueprint $table) {
                if (Schema::hasColumn('gym_attendances', 'otp_code')) {
                    $table->dropColumn('otp_code');
                }
                if (Schema::hasColumn('gym_attendances', 'otp_expires_at')) {
                    $table->dropColumn('otp_expires_at');
                }
                if (Schema::hasColumn('gym_attendances', 'otp_verified')) {
                    $table->dropColumn('otp_verified');
                }
            });
        }

        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'renewed_from_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('renewed_from_id');
            });
        }
    }
}
