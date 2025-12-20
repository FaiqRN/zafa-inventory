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
        Schema::create('user', function (Blueprint $table) {
            $table->id('user_id');
            $table->foreignId('role_id')->constrained('role', 'role_id')->onDelete('cascade');
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('firstname', 100);
            $table->string('lastname', 100);
            $table->text('foto')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat');
            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('telp', 50);
            $table->rememberToken();
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by', 50)->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->string('user_create')->nullable();
            $table->string('user_update')->nullable();
            $table->softDeletes();
        
                  
            $table->index(['firstname', 'lastname']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};