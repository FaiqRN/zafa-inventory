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
        DB::statement("ALTER TABLE `follow_up` MODIFY `source_channel` ENUM('shopee', 'tokopedia', 'whatsapp', 'instagram', 'langsung', 'manual', 'unknown') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `follow_up` MODIFY `source_channel` ENUM('shopee', 'tokopedia', 'whatsapp', 'instagram', 'langsung') NULL");
    }
};
