<?php

namespace App\Exports;

use App\Models\Pengiriman;
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
use Illuminate\Support\Facades\Log;

class PengirimanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize
{
    protected $pengiriman;
    protected $filters;
    
    /**
     * Create a new export instance with filters.
     *
     * @param array $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        
        // Log filter yang digunakan untuk debugging
        Log::info('PengirimanExport filters:', $this->filters);
    }
    
    /**
     * Return collection of pengiriman with applied filters.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Pengiriman::with(['toko', 'barang']);
        
        // Filter by toko_id if provided
        if (!empty($this->filters['toko_id'])) {
            $query->where('toko_id', $this->filters['toko_id']);
        }
        
        // Filter by status if provided
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        
        // Filter by date range if provided
        if (!empty($this->filters['start_date'])) {
            $query->whereDate('tanggal_pengiriman', '>=', $this->filters['start_date']);
        }
        
        if (!empty($this->filters['end_date'])) {
            $query->whereDate('tanggal_pengiriman', '<=', $this->filters['end_date']);
        }
        
        // Sorting
        $sortColumn = !empty($this->filters['sort_column']) ? $this->filters['sort_column'] : 'tanggal_pengiriman';
        $sortDirection = !empty($this->filters['sort_direction']) ? $this->filters['sort_direction'] : 'desc';
        
        // Validasi kolom sorting
        $allowedColumns = [
            'nomer_pengiriman', 'tanggal_pengiriman', 'toko_id', 
            'barang_id', 'jumlah_kirim', 'status'
        ];
        
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'tanggal_pengiriman';
        }
        
        // Apply sorting
        $query->orderBy($sortColumn, $sortDirection);
        
        $collection = $query->get();
        
        // Log untuk debugging
        Log::info('PengirimanExport collection count: ' . $collection->count());
        
        return $collection;
    }
    
    /**
     * Define the sheet title.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Data Pengiriman';
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
            'Tanggal',
            'Toko',
            'Barang',
            'Jumlah',
            'Satuan',
            'Status'
        ];
    }
    
    /**
     * Map each record to a row in the export.
     *
     * @param mixed $pengiriman
     * @return array
     */
    public function map($pengiriman): array
    {
        static $no = 0;
        $no++;
        
        $status = ucfirst($pengiriman->status);
        
        return [
            $no,
            $pengiriman->nomer_pengiriman,
            Carbon::parse($pengiriman->tanggal_pengiriman)->format('d/m/Y'),
            $pengiriman->toko ? $pengiriman->toko->nama_toko : '-',
            $pengiriman->barang ? $pengiriman->barang->nama_barang : '-',
            $pengiriman->jumlah_kirim,
            $pengiriman->barang ? $pengiriman->barang->satuan : '-',
            $status
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
        
        // Align all data cells
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->getStyle('B2:B' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->getStyle('C2:C' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->getStyle('F2:G' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        $sheet->getStyle('H2:H' . $lastRow)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
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
            'A' => 5,     // No
            'B' => 15,    // No. Pengiriman
            'C' => 12,    // Tanggal
            'D' => 25,    // Toko
            'E' => 25,    // Barang
            'F' => 10,    // Jumlah
            'G' => 10,    // Satuan
            'H' => 10,    // Status
        ];
    }
}