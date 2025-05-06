<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ManualCSVImporter
{
    private $processed = 0;
    private $inserted = 0;
    private $updated = 0;
    private $skipped = 0;
    private $errors = [];

    /**
     * Import CSV file manually
     */
    public function import($filePath)
    {
        Log::info('Starting manual CSV import from: ' . $filePath);
        
        try {
            // Baca file CSV secara manual
            $file = fopen($filePath, 'r');
            
            if (!$file) {
                throw new \Exception('Tidak dapat membuka file CSV');
            }
            
            // Baca header
            $headers = fgetcsv($file);
            if (!$headers) {
                throw new \Exception('Format CSV tidak valid - header tidak ditemukan');
            }
            
            // Normalisasi header (lowercase)
            $headers = array_map(function($header) {
                return trim(strtolower($header));
            }, $headers);
            
            Log::info('CSV headers: ' . implode(', ', $headers));
            
            // Baca baris data
            $rowNumber = 1;
            while (($row = fgetcsv($file)) !== false) {
                $rowNumber++;
                
                try {
                    // Skip baris kosong
                    if (count($row) === 0 || (count($row) === 1 && empty($row[0]))) {
                        Log::info('Skipping empty row #' . $rowNumber);
                        $this->skipped++;
                        continue;
                    }
                    
                    // Pastikan jumlah kolom sesuai dengan header
                    if (count($row) !== count($headers)) {
                        Log::warning('Row #' . $rowNumber . ' has ' . count($row) . 
                                    ' columns but header has ' . count($headers) . ' columns');
                    }
                    
                    // Gabungkan header dan data
                    $rowData = [];
                    for ($i = 0; $i < min(count($headers), count($row)); $i++) {
                        $rowData[$headers[$i]] = $row[$i];
                    }
                    
                    // Ekstrak dan format data
                    $data = $this->extractData($rowData);
                    
                    // Skip jika tidak ada nama
                    if (empty($data['nama'])) {
                        Log::info('Skipping row #' . $rowNumber . ' - No name provided');
                        $this->skipped++;
                        continue;
                    }
                    
                    // Cek data yang sudah ada
                    $existingCustomer = null;
                    
                    // Cek berdasarkan email
                    if (!empty($data['email'])) {
                        $existingCustomer = Customer::where('email', $data['email'])->first();
                    }
                    
                    // Jika tidak ditemukan, cek berdasarkan no_tlp
                    if (!$existingCustomer && !empty($data['no_tlp'])) {
                        $existingCustomer = Customer::where('no_tlp', $data['no_tlp'])->first();
                    }
                    
                    // Update atau insert data
                    if ($existingCustomer) {
                        Log::info('Updating existing customer #' . $rowNumber . ': ' . $existingCustomer->customer_id);
                        $existingCustomer->fill($data);
                        $existingCustomer->save();
                        $this->updated++;
                    } else {
                        Log::info('Creating new customer #' . $rowNumber . ': ' . $data['nama']);
                        
                        // Gunakan query builder untuk insert langsung
                        $customerId = DB::table('data_customer')->insertGetId([
                            'nama' => $data['nama'],
                            'gender' => $data['gender'],
                            'usia' => $data['usia'],
                            'alamat' => $data['alamat'],
                            'email' => $data['email'],
                            'no_tlp' => $data['no_tlp'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        
                        Log::info('Created customer with ID: ' . $customerId);
                        $this->inserted++;
                    }
                    
                    $this->processed++;
                    
                } catch (\Exception $e) {
                    Log::error('Error processing row #' . $rowNumber . ': ' . $e->getMessage());
                    $this->errors[] = 'Error on row #' . $rowNumber . ': ' . $e->getMessage();
                }
            }
            
            fclose($file);
            
            Log::info('CSV import completed. Processed: ' . $this->processed . 
                     ', Inserted: ' . $this->inserted . 
                     ', Updated: ' . $this->updated . 
                     ', Skipped: ' . $this->skipped . 
                     ', Errors: ' . count($this->errors));
            
            return [
                'processed' => $this->processed,
                'inserted' => $this->inserted,
                'updated' => $this->updated
            ];
            
        } catch (\Exception $e) {
            Log::error('Error in manual CSV import: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Extract customer data from row
     */
    protected function extractData($rowData)
    {
        // Format data
        return [
            'nama' => $rowData['nama'] ?? null,
            'gender' => isset($rowData['gender']) ? strtoupper(substr($rowData['gender'], 0, 1)) : null,
            'usia' => is_numeric($rowData['usia'] ?? '') ? (int)$rowData['usia'] : null,
            'alamat' => $rowData['alamat'] ?? null,
            'email' => $rowData['email'] ?? null,
            'no_tlp' => $rowData['no_tlp'] ?? $rowData['telepon'] ?? $rowData['no_telepon'] ?? null,
        ];
    }
    
    /**
     * Get the count of processed rows
     */
    public function getProcessedCount()
    {
        return $this->processed;
    }
    
    /**
     * Get the count of inserted rows
     */
    public function getInsertedCount()
    {
        return $this->inserted;
    }
    
    /**
     * Get the count of updated rows
     */
    public function getUpdatedCount()
    {
        return $this->updated;
    }
    
    /**
     * Get the errors that occurred during import
     */
    public function getErrors()
    {
        return $this->errors;
    }
}