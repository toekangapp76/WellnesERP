<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PEAR: tabel daftar mata uang per-bisnis + kurs terhadap IDR + status aktif.
 * Guarded supaya aman dijalankan ulang / di install baru.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('busines_currency')) {
            Schema::create('busines_currency', function (Blueprint $table) {
                $table->id();
                $table->integer('busines_id');
                $table->integer('currency_id');
                $table->decimal('value', 17, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['busines_id', 'currency_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('busines_currency');
    }
};
