<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('follow_up', function (Blueprint $table) {
            $table->id('follow_up_id');
            $table->string('pemesanan_id', 10)->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->enum('target_type', ['pelangganLama', 'pelangganBaru', 'pelangganTidakKembali', 'keseluruhan'])->default('keseluruhan');
            $table->text('message')->nullable();
            $table->json('images')->nullable(); // Store multiple image paths as JSON
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('wablas_message_id')->nullable(); // ID dari Wablas untuk tracking
            $table->text('wablas_response')->nullable(); // Response dari Wablas API
            $table->text('error_message')->nullable(); // Jika ada error
            $table->string('phone_number', 20); // Nomor WhatsApp tujuan
            $table->string('customer_name', 100); // Nama customer
            $table->string('customer_email', 100)->nullable(); // Email customer
            $table->enum('source_channel', ['shopee', 'tokopedia', 'whatsapp', 'instagram', 'langsung'])->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('pemesanan_id')
                  ->references('pemesanan_id')
                  ->on('pemesanan')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
                  
            $table->foreign('customer_id')
                  ->references('customer_id')
                  ->on('data_customer')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
                  
            // Indexes
            $table->index('target_type');
            $table->index('status');
            $table->index('sent_at');
            $table->index('phone_number');
            $table->index('source_channel');
            
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up');
    }
};