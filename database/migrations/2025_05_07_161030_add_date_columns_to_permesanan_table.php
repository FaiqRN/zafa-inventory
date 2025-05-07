<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateColumnsToPermesananTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->date('tanggal_diproses')->nullable()->after('tanggal_pemesanan');
            $table->date('tanggal_dikirim')->nullable()->after('tanggal_diproses');
            $table->date('tanggal_selesai')->nullable()->after('tanggal_dikirim');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pemesanan', function (Blueprint $table) {
            $table->dropColumn(['tanggal_diproses', 'tanggal_dikirim', 'tanggal_selesai']);
        });
    }
}