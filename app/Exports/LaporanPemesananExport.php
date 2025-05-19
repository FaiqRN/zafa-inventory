<?php

namespace App\Exports;

use App\Models\Pemesanan;
use App\Models\Barang;
use App\Models\PemesananLaporan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanPemesananExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $tipe;
    protected $startDate;
    protected $endDate;
    protected $detailId;
    
    public function __construct($tipe, $startDate, $endDate, $detailId = null)
    {
        $this->tipe = $tipe;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->detailId = $detailId;
    }
    
    public function collection()
    {
        // Base query for all types
        $query = DB::table('pemesanan')
            ->join('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
            ->whereBetween('pemesanan.tanggal_pemesanan', [$this->startDate, $this->endDate])
            ->where('pemesanan.status_pemesanan', 'selesai');
        
        if ($this->detailId) {
            // Detail mode - get specific entries
            if ($this->tipe === 'barang') {
                $query->where('pemesanan.barang_id', $this->detailId);
            } elseif ($this->tipe === 'sumber') {
                $query->where('pemesanan.pemesanan_dari', $this->detailId);
            } elseif ($this->tipe === 'pemesan') {
                $query->where('pemesanan.nama_pemesan', $this->detailId);
            }
            
            return $query->select(
                'pemesanan.pemesanan_id',
                'pemesanan.tanggal_pemesanan',
                'barang.nama_barang',
                'pemesanan.nama_pemesan',
                'pemesanan.jumlah_pesanan',
                'pemesanan.total',
                'pemesanan.pemesanan_dari',
                'pemesanan.status_pemesanan'
            )->orderBy('pemesanan.tanggal_pemesanan', 'desc')->get();
            
        } else {
            // Summary mode - get aggregated data
            if ($this->tipe === 'barang') {
                // Group by Barang (Product)
                return $query->select(
                    'barang.barang_id',
                    'barang.nama_barang',
                    DB::raw('COUNT(pemesanan.pemesanan_id) as jumlah_pesanan'),
                    DB::raw('SUM(pemesanan.jumlah_pesanan) as total_unit'),
                    DB::raw('SUM(pemesanan.total) as total_pendapatan')
                )
                ->groupBy('barang.barang_id', 'barang.nama_barang')
                ->get();
                
            } elseif ($this->tipe === 'sumber') {
                // Group by Sumber Pemesanan (Order Source)
                return $query->select(
                    'pemesanan.pemesanan_dari',
                    DB::raw('COUNT(pemesanan.pemesanan_id) as jumlah_pesanan'),
                    DB::raw('SUM(pemesanan.jumlah_pesanan) as total_unit'),
                    DB::raw('SUM(pemesanan.total) as total_pendapatan')
                )
                ->groupBy('pemesanan.pemesanan_dari')
                ->get();
                
            } elseif ($this->tipe === 'pemesan') {
                // Group by Nama Pemesan (Customer Name)
                return $query->select(
                    'pemesanan.nama_pemesan',
                    DB::raw('COUNT(pemesanan.pemesanan_id) as jumlah_pesanan'),
                    DB::raw('SUM(pemesanan.jumlah_pesanan) as total_unit'),
                    DB::raw('SUM(pemesanan.total) as total_pendapatan')
                )
                ->groupBy('pemesanan.nama_pemesan')
                ->get();
            }
        }
        
        // Fallback empty collection
        return collect([]);
    }
    
    public function headings(): array
    {
        if ($this->detailId) {
            // Detail mode - show individual orders
            return [
                'ID Pemesanan',
                'Tanggal Pemesanan',
                'Nama Barang',
                'Nama Pemesan',
                'Jumlah Pesanan',
                'Total (Rp)',
                'Sumber Pemesanan',
                'Status'
            ];
        } else {
            // Summary mode - show aggregated data
            if ($this->tipe === 'barang') {
                return [
                    'ID Barang',
                    'Nama Barang',
                    'Jumlah Pesanan',
                    'Total Unit',
                    'Total Pendapatan (Rp)'
                ];
            } elseif ($this->tipe === 'sumber') {
                return [
                    'Sumber Pemesanan',
                    'Jumlah Pesanan',
                    'Total Unit',
                    'Total Pendapatan (Rp)'
                ];
            } elseif ($this->tipe === 'pemesan') {
                return [
                    'Nama Pemesan',
                    'Jumlah Pesanan',
                    'Total Unit',
                    'Total Pendapatan (Rp)'
                ];
            }
        }
        
        // Fallback empty array
        return [];
    }
    
    public function map($row): array
    {
        if ($this->detailId) {
            // Detail mode - map individual orders
            return [
                $row->pemesanan_id,
                $row->tanggal_pemesanan,
                $row->nama_barang,
                $row->nama_pemesan,
                $row->jumlah_pesanan,
                $row->total,
                $row->pemesanan_dari,
                $row->status_pemesanan
            ];
        } else {
            // Summary mode - map aggregated data
            if ($this->tipe === 'barang') {
                return [
                    $row->barang_id,
                    $row->nama_barang,
                    $row->jumlah_pesanan,
                    $row->total_unit,
                    $row->total_pendapatan
                ];
            } elseif ($this->tipe === 'sumber') {
                return [
                    $row->pemesanan_dari,
                    $row->jumlah_pesanan,
                    $row->total_unit,
                    $row->total_pendapatan
                ];
            } elseif ($this->tipe === 'pemesan') {
                return [
                    $row->nama_pemesan,
                    $row->jumlah_pesanan,
                    $row->total_unit,
                    $row->total_pendapatan
                ];
            }
        }
        
        // Fallback empty array
        return [];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    public function title(): string
    {
        if ($this->detailId) {
            return 'Detail ' . ucfirst($this->tipe);
        } else {
            return 'Laporan Per ' . ucfirst($this->tipe);
        }
    }
}