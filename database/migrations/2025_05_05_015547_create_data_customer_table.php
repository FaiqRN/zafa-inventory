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
        Schema::create('data_customer', function (Blueprint $table) {
            $table->id('customer_id');
            $table->string('nama', 100)->nullable();
            $table->integer('usia')->nullable();
            $table->enum('gender', ['L', 'P'])->nullable();
            $table->text('alamat')->nullable();
            $table->string('email', 100)->nullable()->unique();
            $table->string('no_tlp', 20)->nullable()->unique();
            $table->string('pemesanan_id', 10)->nullable();
            $table->timestamps();
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
        
        // Create a trigger to automatically fill customer data from pemesanan
        DB::unprepared('
            CREATE TRIGGER fill_data_customer_from_pemesanan
            AFTER INSERT ON pemesanan
            FOR EACH ROW
            BEGIN
                INSERT INTO data_customer (nama, alamat, email, no_tlp, pemesanan_id, created_at, updated_at)
                VALUES (NEW.nama_pemesan, NEW.alamat_pemesan, NEW.email_pemesan, NEW.no_telp_pemesan, NEW.pemesanan_id, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                    nama = COALESCE(nama, NEW.nama_pemesan),
                    alamat = COALESCE(alamat, NEW.alamat_pemesan),
                    email = COALESCE(email, NEW.email_pemesan),
                    no_tlp = COALESCE(no_tlp, NEW.no_telp_pemesan),
                    pemesanan_id = NEW.pemesanan_id,
                    updated_at = NOW();
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus trigger terlebih dahulu
        DB::unprepared('DROP TRIGGER IF EXISTS fill_data_customer_from_pemesanan');
        
        // Kemudian hapus tabel
        Schema::dropIfExists('data_customer');
    }
};