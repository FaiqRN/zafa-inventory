<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('data_customer')) {
            Schema::create('data_customer', function (Blueprint $table) {
                $table->id('customer_id');
                $table->string('nama', 100)->nullable();
                $table->integer('usia')->nullable();
                $table->enum('gender', ['L', 'P'])->nullable();
                $table->text('alamat')->nullable();
                $table->string('email', 100)->nullable()->unique();
                $table->string('no_tlp', 20)->nullable()->unique();
                $table->string('pemesanan_id', 10)->nullable();
                
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
                $table->string('user_create')->nullable();
                $table->string('user_update')->nullable();
                $table->softDeletes();
                
                $table->foreign('pemesanan_id')
                      ->references('pemesanan_id')
                      ->on('pemesanan')
                      ->onDelete('set null')
                      ->onUpdate('cascade');
                      
                $table->index('nama');
                $table->index('email');
                
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_general_ci';
            });
        }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_customer');
    }
};