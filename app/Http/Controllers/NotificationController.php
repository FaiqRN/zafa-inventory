<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Pengiriman;
use App\Models\Toko;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{

    private const SETTINGS_FILE = 'settings/notification.json';

    private const DEFAULT_SETTINGS = [
        'stock_threshold' => 0,
        'pending_return_days' => 12,
        'return_deadline_days' => 14,
        'check_interval' => 60,
    ];

    private function getSettings()
    {
        $disk = Storage::disk('local');

        if (!$disk->exists(self::SETTINGS_FILE)) {
            return self::DEFAULT_SETTINGS;
        }

        try {
            $content = $disk->get(self::SETTINGS_FILE);
            $settings = json_decode($content, true);
            return array_merge(self::DEFAULT_SETTINGS, $settings ?? []);
        } catch (\Exception $e) {
            return self::DEFAULT_SETTINGS;
        }
    }

    public function getNotifications(Request $request)
    {
        $settings = $this->getSettings();
        $notifications = [];
        $today = Carbon::now()->startOfDay();

        // 1. Check for low/empty stock based on threshold setting
        // Hitung stok secara agregat di database untuk menghindari N+1 query.
        $stockThreshold = $settings['stock_threshold'];
        $barangLowStock = Barang::query()
            ->withStok()
            ->havingRaw('COALESCE(stok, 0) <= ?', [$stockThreshold])
            ->get();
        
        foreach ($barangLowStock as $barang) {
            $stok = $barang->stok;
            $title = $stok == 0 ? 'Stok Barang Kosong' : 'Stok Barang Menipis';
            $message = $stok == 0
                ? "Stok \"{$barang->{Barang::FIELD_NAMA_BARANG}}\" ({$barang->{Barang::FIELD_BARANG_KODE}}) habis."
                : "Stok \"{$barang->{Barang::FIELD_NAMA_BARANG}}\" ({$barang->{Barang::FIELD_BARANG_KODE}}) tersisa {$stok} unit.";
            
            $notifications[] = [
                'id' => 'stock_' . $barang->{Barang::FIELD_BARANG_ID},
                'type' => 'stock_low',
                'title' => $title,
                'message' => $message,
                'icon' => 'fas fa-box-open',
                'icon_color' => $stok == 0 ? 'danger' : 'warning',
                'url' => route('barang.index'),
                'created_at' => now()->toISOString(),
            ];
        }

        // 2. Check for pending returns based on settings
        // Acuan waktu retur dihitung dari tanggal_terima (barang diterima toko)
        $pendingReturnDays = $settings['pending_return_days'];
        $returnDeadlineDays = $settings['return_deadline_days'];
        $warningDate = $today->copy()->subDays($pendingReturnDays);
        
        $pendingPengiriman = Pengiriman::query()
            ->select([
                Pengiriman::FIELD_NOMER_PENGIRIMAN,
                Pengiriman::FIELD_TOKO_ID,
            ])
            ->selectRaw('MIN(' . Pengiriman::FIELD_TANGGAL_TERIMA . ') as tanggal_acuan_retur')
            ->selectRaw('COUNT(*) as total_barang_pending')
            ->where(Pengiriman::FIELD_STATUS, 'terkirim')
            ->whereNotNull(Pengiriman::FIELD_TANGGAL_TERIMA)
            ->whereDate(Pengiriman::FIELD_TANGGAL_TERIMA, '<=', $warningDate->toDateString())
            ->whereDoesntHave('retur')
            ->groupBy(Pengiriman::FIELD_NOMER_PENGIRIMAN, Pengiriman::FIELD_TOKO_ID)
            ->with(['toko'])
            ->get();

        foreach ($pendingPengiriman as $pengiriman) {
            $tanggalTerima = $pengiriman->tanggal_acuan_retur;

            if (empty($tanggalTerima)) {
                continue;
            }

            $tanggalAcuanRetur = Carbon::parse($tanggalTerima)->startOfDay();
            $batasRetur = $tanggalAcuanRetur->copy()->addDays($returnDeadlineDays)->startOfDay();
            $sisaHari = (int) $today->diffInDays($batasRetur, false);

            $nomerPengiriman = $pengiriman->{Pengiriman::FIELD_NOMER_PENGIRIMAN} ?? '-';
            $tokoId = $pengiriman->{Pengiriman::FIELD_TOKO_ID} ?? '-';
            $tokoNama = $pengiriman->toko ? $pengiriman->toko->{Toko::FIELD_NAMA_TOKO} : 'Unknown';
            $totalBarangPending = (int) ($pengiriman->total_barang_pending ?? 0);

            if ($sisaHari > 0) {
                $timeInfo = "{$sisaHari} hari lagi";
            } elseif ($sisaHari == 0) {
                $timeInfo = "HARI INI!";
            } else {
                $timeInfo = "Terlambat " . abs($sisaHari) . " hari";
            }

            $iconColor = $sisaHari <= 0 ? 'danger' : 'warning';

            $notifications[] = [
                'id' => 'retur_' . md5($nomerPengiriman . '|' . $tokoId),
                'type' => 'pending_return',
                'title' => 'Pengiriman Belum Diretur',
                'message' => "#{$nomerPengiriman} ke {$tokoNama}. {$totalBarangPending} barang belum diretur. Batas: {$timeInfo}",
                'icon' => 'fas fa-truck',
                'icon_color' => $iconColor,
                'url' => route('retur.index'),
                'created_at' => $tanggalAcuanRetur->toISOString(),
            ];
        }

        // Sort by urgency (danger first, then warning)
        usort($notifications, function($a, $b) {
            $order = ['danger' => 0, 'warning' => 1, 'info' => 2, 'primary' => 3];
            return ($order[$a['icon_color']] ?? 99) - ($order[$b['icon_color']] ?? 99);
        });

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'total' => count($notifications),
            'settings' => [
                'check_interval' => $settings['check_interval'],
            ],
        ]);
    }
}
