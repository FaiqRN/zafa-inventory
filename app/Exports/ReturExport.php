<?php

namespace App\Exports;

use App\Models\Retur;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class ReturExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    protected $filters;
    
    /**
     * Create a new export instance with filters.
     *
     * @param array $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }
    
    /**
     * Return collection of retur with applied filters.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Retur::with(['toko', 'barang']);
        
        // Filter by toko_id if provided
        if (!empty($this->filters['toko_id'])) {
            $query->where('toko_id', $this->filters['toko_id']);
        }
        
        // Filter by barang_id if provided
        if (!empty($this->filters['barang_id'])) {
            $query->where('barang_id', $this->filters['barang_id']);
        }
        
        // Filter by date range if provided
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('tanggal_retur', '>=', $this->filters['start_date']);
        }
        
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('tanggal_retur', '<=', $this->filters['end_date']);
        }
        
        return $query->orderBy('tanggal_retur', 'desc')->get();
    }
    
    /**
     * Define the sheet title.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Data Retur Barang';
    }
    
    /**
     * Define headings.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'No. Pengiriman',
            'Tanggal Pengiriman',
            'Tanggal Retur',
            'Toko',
            'Barang',
            'Jumlah Kirim',
            'Jumlah Retur',
            'Total Terjual',
            'Harga Satuan',
            'Total Hasil',
            'Kondisi',
            'Keterangan'
        ];
    }
    
    /**
     * Map each record to a row in the export.
     *
     * @param mixed $retur
     * @return array
     */
    public function map($retur): array
    {
        static $no = 0;
        $no++;
        
        return [
            $no,
            $retur->nomer_pengiriman,
            Carbon::parse($retur->tanggal_pengiriman)->format('d/m/Y'),
            Carbon::parse($retur->tanggal_retur)->format('d/m/Y'),
            $retur->toko ? $retur->toko->nama_toko : '-',
            $retur->barang ? $retur->barang->nama_barang : '-',
            $retur->jumlah_kirim,
            $retur->jumlah_retur,
            $retur->total_terjual,
            'Rp ' . number_format($retur->harga_awal_barang, 0, ',', '.'),
            'Rp ' . number_format($retur->hasil, 0, ',', '.'),
            $retur->kondisi,
            $retur->keterangan
        ];
    }
    
    /**
     * Style the worksheet.
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $sheet->getHighestColumn();
        $range = 'A1:' . $lastColumn . $lastRow;
        
        // Apply style to all cells to ensure Excel data is properly formatted
        $sheet->getStyle($range)->getAlignment()->setWrapText(true);
        
        // Add borders to all cells
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);
        
        // Style for header row
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        
        // Align No. column
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Align No. Pengiriman column
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Align date columns
        $sheet->getStyle('C2:D' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Align numeric columns
        $sheet->getStyle('G2:I' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Align price columns
        $sheet->getStyle('J2:K' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        return [];
    }
    
    /**
     * Define column widths.
     *
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 5,      // No
            'B' => 15,     // No. Pengiriman
            'C' => 15,     // Tanggal Pengiriman
            'D' => 15,     // Tanggal Retur
            'E' => 25,     // Toko
            'F' => 25,     // Barang
            'G' => 10,     // Jumlah Kirim
            'H' => 10,     // Jumlah Retur
            'I' => 10,     // Total Terjual
            'J' => 15,     // Harga Satuan
            'K' => 15,     // Total Hasil
            'L' => 15,     // Kondisi
            'M' => 30,     // Keterangan
        ];
    }
}