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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->index();
            $table->string('ip_address', 45)->index();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('attempted_at')->useCurrent();
            $table->timestamp('locked_until')->nullable()->index();
            
            // Indexes untuk performa
            $table->index(['username', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
