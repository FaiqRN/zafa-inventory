<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KonfigurasiSistem;
use Spatie\Permission\Models\Permission;
use App\Helpers\RoleHelper;
use App\Models\Toko;

class KonfigurasiIntervalKirimSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── 1. Seed nilai konfigurasi default ────────────────────────────────
        KonfigurasiSistem::updateOrCreate(
            ['key' => KonfigurasiSistem::KEY_MIN_INTERVAL_KIRIM_HARI],
            [
                'nilai'      => (string) KonfigurasiSistem::DEFAULT_MIN_INTERVAL_KIRIM_HARI,
                'tipe'       => 'integer',
                'label'      => 'Interval Minimum Pengiriman (Hari)',
                'keterangan' => 'Interval minimum pengiriman dalam satuan hari. '
                    . 'Sistem tidak akan merekomendasikan pengiriman lebih sering '
                    . 'dari nilai ini. Nilai 0 = tidak ada batasan. Default: 14.',
            ]
        );

        $this->command->info('✓ Konfigurasi min_interval_kirim_hari berhasil di-seed (default: 14 hari)');

        // ── 1b. Set default per-toko jika masih 0/null ─────────────────────
        $affected = Toko::where(Toko::FIELD_MIN_INTERVAL_KIRIM_HARI, 0)
            ->orWhereNull(Toko::FIELD_MIN_INTERVAL_KIRIM_HARI)
            ->update([
                Toko::FIELD_MIN_INTERVAL_KIRIM_HARI => KonfigurasiSistem::DEFAULT_MIN_INTERVAL_KIRIM_HARI,
            ]);

        $this->command->info("✓ {$affected} toko di-set min_interval_kirim_hari = "
            . KonfigurasiSistem::DEFAULT_MIN_INTERVAL_KIRIM_HARI);

        // ── 2. Buat permission baru ───────────────────────────────────────────
        $permissions = [
            'view-config-interval-kirim'   => 'Lihat Konfigurasi Interval Pengiriman',
            'update-config-interval-kirim' => 'Update Konfigurasi Interval Pengiriman',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $this->command->info("✓ Permission '{$name}' berhasil dibuat");
        }

        // ── 3. Pastikan Admin tetap memiliki semua permission ────────────────
        RoleHelper::ensureAdminHasAllPermissions();
        $this->command->info('✓ Admin role verified — has ALL permissions');
    }
}
