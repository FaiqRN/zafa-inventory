<?php

namespace App\Exports;

use App\Models\Toko;
use App\Models\Pengiriman;
use App\Models\Retur;
use App\Models\TokoLaporan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class LaporanTokoExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
{
    protected $periode;
    protected $bulan;
    protected $tahun;
    protected $startDate;
    protected $endDate;
    
    public function __construct($periode, $bulan, $tahun, $startDate, $endDate)
    {
        $this->periode = $periode;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
    
    public function collection()
    {
        $tokos = Toko::all();
        $result = collect();
        
        foreach ($tokos as $toko) {
            // Get total penjualan
            $totalPenjualan = Retur::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_pengiriman', [$this->startDate, $this->endDate])
                ->sum(DB::raw('total_terjual * harga_awal_barang'));
            
            // Get total pengiriman
            $totalPengiriman = Pengiriman::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_pengiriman', [$this->startDate, $this->endDate])
                ->count();
            
            // Get total barang retur
            $totalRetur = Retur::where('toko_id', $toko->toko_id)
                ->whereBetween('tanggal_retur', [$this->startDate, $this->endDate])
                ->sum('jumlah_retur');
            
            // Get existing notes
            $laporan = TokoLaporan::where('toko_id', $toko->toko_id)
                ->where('periode', $this->periode)
                ->where('bulan', $this->bulan)
                ->where('tahun', $this->tahun)
                ->first();
            
            $catatan = $laporan ? $laporan->catatan : '';
            
            $result->push([
                'toko_id' => $toko->toko_id,
                'nama_toko' => $toko->nama_toko,
                'pemilik' => $toko->pemilik,
                'alamat' => $toko->alamat,
                'nomer_telpon' => $toko->nomer_telpon,
                'total_penjualan' => $totalPenjualan,
                'total_pengiriman' => $totalPengiriman,
                'total_retur' => $totalRetur,
                'catatan' => $catatan
            ]);
        }
        
        return $result;
    }
    
    public function headings(): array
    {
        return [
            'ID Toko',
            'Nama Toko',
            'Pemilik',
            'Alamat',
            'Nomor Telepon',
            'Total Penjualan (Rp)',
            'Total Pengiriman',
            'Total Barang Retur',
            'Catatan'
        ];
    }
    
    public function map($row): array
    {
        return [
            $row['toko_id'],
            $row['nama_toko'],
            $row['pemilik'],
            $row['alamat'],
            $row['nomer_telpon'],
            $row['total_penjualan'],
            $row['total_pengiriman'],
            $row['total_retur'],
            $row['catatan']
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
    
    public function title(): string
    {
        if ($this->periode == '1_bulan') {
            return 'Laporan 1 Bulan';
        } elseif ($this->periode == '6_bulan') {
            return 'Laporan 6 Bulan';
        } else {
            return 'Laporan 1 Tahun';
        }
    }
}