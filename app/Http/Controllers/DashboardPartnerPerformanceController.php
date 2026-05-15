<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\dashboardPartnerPerformance\DashboardPartnerPerformanceHelper;

class DashboardPartnerPerformanceController extends Controller
{
    /**
     * Menampilkan dashboard utama dengan 4 komponen visualisasi.
     *
     */
    public function index()
    {
        return DashboardPartnerPerformanceHelper::index();
    }

    /**
     * 1. Grafik Pengiriman Barang (Line Chart)
     * Data: Timeline pengiriman barang per tahun
     * 
     */
    public function getGrafikPengiriman(Request $request)
    {
        return DashboardPartnerPerformanceHelper::getGrafikPengiriman($request);
    }

    /**
     * 2. Barang Laku/Tidak Laku (Bar Chart Interaktif) - DIPERBAIKI TOTAL
     * Filter: barang laku vs tidak laku berdasarkan data pengiriman
     * 
     */
    public function getBarangLakuTidakLaku(Request $request)
    {
        return DashboardPartnerPerformanceHelper::getBarangLakuTidakLaku($request);
    }

    /**
     * 3. Transaksi Terbaru Pengiriman (Tabel Data)
     * Data pengiriman terbaru
     * 
     */
    public function getTransaksiTerbaru(Request $request)
    {
        return DashboardPartnerPerformanceHelper::getTransaksiTerbaru($request);
    }

    /**
     * 4. Toko dengan Retur Terbanyak - DIPERBAIKI UNTUK MENAMPILKAN DATA HISTORIS
     * 
     */
    public function getTokoReturTerbanyak(Request $request)
    {
        return DashboardPartnerPerformanceHelper::getTokoReturTerbanyak($request);
    }

    /**
     * API untuk mendapatkan ringkasan statistik dashboard
     * 
     */
    public function getStatistikRingkasan()
    {
        return DashboardPartnerPerformanceHelper::getStatistikRingkasan();
    }
}