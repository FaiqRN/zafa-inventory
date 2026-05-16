<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_monitor_logs', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->nullable()->comment('Username user yang melakukan aksi');
            $table->string('action', 20)->comment('create | update | delete');
            $table->string('module', 100)->comment('Nama modul / tabel yang dikenai aksi');
            $table->string('description')->nullable()->comment('Deskripsi singkat aktivitas');
            $table->json('old_data')->nullable()->comment('Data sebelum perubahan (update/delete)');
            $table->json('new_data')->nullable()->comment('Data sesudah perubahan (create/update)');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['username', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('module');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_monitor_logs');
    }
};
