<?php

namespace App\Exports;

use App\Models\Pengiriman;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PengirimanExportCsv implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    /**
     * Constructor with filter parameters.
     *
     * @param array $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Fetch data from database with applied filters.
     */
    public function collection()
    {
        $query = Pengiriman::with(['toko', 'barang']);

        if (!empty($this->filters['toko_id'])) {
            $query->where('toko_id', $this->filters['toko_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('tanggal_pengiriman', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('tanggal_pengiriman', '<=', $this->filters['end_date']);
        }

        return $query->orderBy('tanggal_pengiriman', 'desc')->get();
    }

    /**
     * Define column headings.
     */
    public function headings(): array
    {
        return [
            'No',
            'No. Pengiriman',
            'Tanggal',
            'Toko',
            'Barang',
            'Jumlah',
            'Satuan',
            'Status',
        ];
    }

    /**
     * Map each row of data for export.
     */
    public function map($pengiriman): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $pengiriman->nomer_pengiriman,
            Carbon::parse($pengiriman->tanggal_pengiriman)->format('d/m/Y'),
            $pengiriman->toko ? $pengiriman->toko->nama_toko : '-',
            $pengiriman->barang ? $pengiriman->barang->nama_barang : '-',
            $pengiriman->jumlah_kirim,
            $pengiriman->barang ? $pengiriman->barang->satuan : '-',
            ucfirst($pengiriman->status),
        ];
    }
}
