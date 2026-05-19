<?php

namespace App\Http\Controllers;

use App\Models\KonfigurasiSistem;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use App\Helpers\DashboardMonitorLogger;
use stdClass;

class KonfigurasiIntervalKirimController extends Controller
{
    /**
     * Cek apakah permission sudah ada di DB.
     * Mencegah PermissionDoesNotExist saat seeder belum dijalankan.
     */
    private static function permissionExists(string $name): bool
    {
        return Permission::where('name', $name)->where('guard_name', 'web')->exists();
    }

    /**
     * Gate check aman — return 403 jika tidak punya akses atau permission belum ada.
     */
    private function authorizeView(): void
    {
        if (!self::permissionExists('view-config-interval-kirim')) {
            abort(403, 'Permission belum dikonfigurasi. Jalankan: php artisan zafa:seed-interval-kirim');
        }
        abort_unless(Gate::allows('view-config-interval-kirim'), 403);
    }

    private function authorizeUpdate(): void
    {
        if (!self::permissionExists('update-config-interval-kirim')) {
            abort(403, 'Permission belum dikonfigurasi. Jalankan: php artisan zafa:seed-interval-kirim');
        }
        abort_unless(Gate::allows('update-config-interval-kirim'), 403);
    }

    /**
     * Tampilkan halaman konfigurasi interval kirim.
     */
    public function index()
    {
        $this->authorizeView();

        $konfigurasi = KonfigurasiSistem::where(
            'key',
            KonfigurasiSistem::KEY_MIN_INTERVAL_KIRIM_HARI
        )->first();

        // Jika baris belum ada (misal seeder belum jalan), buat nilai default in-memory
        if (!$konfigurasi) {
            $konfigurasi = new KonfigurasiSistem([
                'key'        => KonfigurasiSistem::KEY_MIN_INTERVAL_KIRIM_HARI,
                'nilai'      => (string) KonfigurasiSistem::DEFAULT_MIN_INTERVAL_KIRIM_HARI,
                'tipe'       => 'integer',
                'label'      => 'Interval Minimum Pengiriman (Hari)',
                'keterangan' => 'Interval minimum pengiriman dalam satuan hari. '
                    . 'Nilai 0 = tidak ada batasan. Default: 14.',
            ]);
        }

        $breadcrumb        = new stdClass();
        $breadcrumb->title = 'Konfigurasi Interval Pengiriman';
        $breadcrumb->list  = ['Home', 'Sistem Pengaturan', 'Konfigurasi Interval Pengiriman'];

        $canUpdate = self::permissionExists('update-config-interval-kirim')
            && Gate::allows('update-config-interval-kirim');

        return view('config-interval-kirim.index', [
            'activemenu'   => 'config-interval-kirim',
            'breadcrumb'   => $breadcrumb,
            'konfigurasi'  => $konfigurasi,
            'defaultValue' => KonfigurasiSistem::DEFAULT_MIN_INTERVAL_KIRIM_HARI,
            'canUpdate'    => $canUpdate,
        ]);
    }

    /**
     * Ambil nilai konfigurasi saat ini (JSON API untuk AJAX).
     */
    public function show(): JsonResponse
    {
        $this->authorizeView();

        $nilai = KonfigurasiSistem::get(
            KonfigurasiSistem::KEY_MIN_INTERVAL_KIRIM_HARI,
            KonfigurasiSistem::DEFAULT_MIN_INTERVAL_KIRIM_HARI
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'key'           => KonfigurasiSistem::KEY_MIN_INTERVAL_KIRIM_HARI,
                'nilai'         => $nilai,
                'default_value' => KonfigurasiSistem::DEFAULT_MIN_INTERVAL_KIRIM_HARI,
            ],
        ]);
    }

    /**
     * Update nilai min_interval_kirim_hari.
     *
     * Validasi: integer, minimal 0 (0 = tidak ada batasan), maksimal 365.
     */
    public function update(Request $request): JsonResponse
    {
        $this->authorizeUpdate();

        $request->validate([
            'nilai' => 'required|integer|min:0|max:365',
        ], [
            'nilai.required' => 'Nilai interval wajib diisi.',
            'nilai.integer'  => 'Nilai interval harus berupa bilangan bulat.',
            'nilai.min'      => 'Nilai interval minimal adalah 0 hari (0 = tidak ada batasan).',
            'nilai.max'      => 'Nilai interval maksimal adalah 365 hari.',
        ]);

        try {
            $userId = Auth::user()?->{User::FIELD_USER_ID};
            $konfigurasi = KonfigurasiSistem::set(
                KonfigurasiSistem::KEY_MIN_INTERVAL_KIRIM_HARI,
                (int) $request->nilai,
                $userId !== null ? (int) $userId : null
            );

            // Pastikan label & tipe tetap benar jika baris baru
            if ($konfigurasi->wasRecentlyCreated) {
                $konfigurasi->update([
                    'tipe'       => 'integer',
                    'label'      => 'Interval Minimum Pengiriman (Hari)',
                    'keterangan' => 'Interval minimum pengiriman dalam satuan hari. '
                        . 'Nilai 0 = tidak ada batasan. Default: 14.',
                ]);
            }

            // Sync ke semua toko agar mengikuti nilai global terbaru.
            $affectedToko = Toko::query()->update([
                Toko::FIELD_MIN_INTERVAL_KIRIM_HARI => (int) $request->nilai,
            ]);

            DashboardMonitorLogger::update(
                'Konfigurasi Interval Kirim',
                "Update interval pengiriman menjadi {$request->nilai} hari",
                null,
                ['nilai' => (int) $request->nilai, 'toko_updated' => $affectedToko],
                $request
            );

            return response()->json([
                'success' => true,
                'message' => 'Konfigurasi interval pengiriman berhasil diperbarui.',
                'data'    => [
                    'key'   => KonfigurasiSistem::KEY_MIN_INTERVAL_KIRIM_HARI,
                    'nilai' => (int) $request->nilai,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui konfigurasi: ' . $e->getMessage(),
            ], 500);
        }
    }
}

