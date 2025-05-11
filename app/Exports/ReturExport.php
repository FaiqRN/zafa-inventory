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
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReturExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, ShouldAutoSize, WithCustomCsvSettings
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
        try {
            $query = Retur::with(['toko', 'barang']);
            
            Log::info('Starting export collection with filters:', $this->filters);
            
            // Filter by toko_id if provided
            if (!empty($this->filters['toko_id'])) {
                $query->where('toko_id', $this->filters['toko_id']);
                Log::info('Applied toko_id filter: ' . $this->filters['toko_id']);
            }
            
            // Filter by barang_id if provided
            if (!empty($this->filters['barang_id'])) {
                $query->where('barang_id', $this->filters['barang_id']);
                Log::info('Applied barang_id filter: ' . $this->filters['barang_id']);
            }
            
            // Filter by date range if provided
            if (!empty($this->filters['start_date'])) {
                $query->whereDate('tanggal_retur', '>=', $this->filters['start_date']);
                Log::info('Applied start_date filter: ' . $this->filters['start_date']);
            }
            
            if (!empty($this->filters['end_date'])) {
                $query->whereDate('tanggal_retur', '<=', $this->filters['end_date']);
                Log::info('Applied end_date filter: ' . $this->filters['end_date']);
            }
            
            // Get the results
            $results = $query->orderBy('tanggal_retur', 'desc')->get();
            
            Log::info('Export collection returned ' . $results->count() . ' records');
            
            return $results;
        } catch (\Exception $e) {
            Log::error('Error in export collection: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            // Return empty collection on error to avoid breaking the export
            return collect([]);
        }
    }
    
    /**
     * Define CSV settings
     *
     * @return array
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_character' => '\\',
            'use_bom' => true,
            'include_separator_line' => false,
            'excel_compatibility' => true,
        ];
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
        
        try {
            return [
                $no,
                $retur->nomer_pengiriman ?? '-',
                !empty($retur->tanggal_pengiriman) ? Carbon::parse($retur->tanggal_pengiriman)->format('d/m/Y') : '-',
                !empty($retur->tanggal_retur) ? Carbon::parse($retur->tanggal_retur)->format('d/m/Y') : '-',
                $retur->toko ? $retur->toko->nama_toko : '-',
                $retur->barang ? $retur->barang->nama_barang : '-',
                $retur->jumlah_kirim ?? 0,
                $retur->jumlah_retur ?? 0,
                $retur->total_terjual ?? 0,
                $retur->harga_awal_barang ?? 0, // For CSV, use numeric values without formatting
                $retur->hasil ?? 0, // For CSV, use numeric values without formatting
                $retur->kondisi ?? '-',
                $retur->keterangan ?? '-'
            ];
        } catch (\Exception $e) {
            Log::error('Error mapping row: ' . $e->getMessage());
            Log::error('Retur data: ' . json_encode($retur));
            
            // Return default row if error occurs
            return [
                $no,
                '-',
                '-',
                '-',
                '-',
                '-',
                0,
                0,
                0,
                0,
                0,
                '-',
                'Error'
            ];
        }
    }
    
    /**
     * Style the worksheet (only applies to Excel, not CSV).
     *
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        try {
            $lastRow = $sheet->getHighestRow();
            $lastColumn = $sheet->getHighestColumn();
            $range = 'A1:' . $lastColumn . $lastRow;
            
            // Only apply styles if we're not exporting as CSV
            if (!app()->runningInConsole() && !request()->has('format') || request('format') !== 'csv') {
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
                
                // Apply number formatting for currency columns
                $sheet->getStyle('J2:K' . $lastRow)->getNumberFormat()
                      ->setFormatCode('#,##0.00');
                
                // Align columns
                if ($lastRow > 1) {
                    $sheet->getStyle('A2:A' . $lastRow)->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    $sheet->getStyle('B2:B' . $lastRow)->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    $sheet->getStyle('C2:D' . $lastRow)->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    
                    $sheet->getStyle('G2:I' . $lastRow)->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    
                    $sheet->getStyle('J2:K' . $lastRow)->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error applying styles: ' . $e->getMessage());
        }
        
        return [];
    }
    
    /**
     * Define column widths (only applies to Excel, not CSV).
     *
     * @return array
     */
    public function columnWidths(): array
    {
        // Only apply column widths for Excel format
        if (!app()->runningInConsole() && request()->has('format') && request('format') === 'csv') {
            return [];
        }
        
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