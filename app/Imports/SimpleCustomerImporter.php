<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SimpleCustomerImporter implements ToCollection, WithHeadingRow
{
    private $processed = 0;
    private $inserted = 0;
    private $updated = 0;
    private $skipped = 0;
    private $errors = [];

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        // Log the total number of rows
        Log::info('Started processing ' . $rows->count() . ' rows');

        // Disable foreign key checks temporarily if needed
        // DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($rows as $index => $row) {
            // Log each row for debugging
            Log::info('Processing row #' . ($index + 1) . ': ' . json_encode($row->toArray()));
            
            try {
                // Normalize the row data
                $data = $this->normalizeRowData($row);
                
                // Skip rows with empty required fields
                if (empty($data['nama'])) {
                    Log::info('Skipping row #' . ($index + 1) . ' - Empty name');
                    $this->skipped++;
                    continue;
                }
                
                // Check for existing record
                $existingRecord = null;
                
                // Try to find by email if available
                if (!empty($data['email'])) {
                    $existingRecord = Customer::where('email', $data['email'])->first();
                    if ($existingRecord) {
                        Log::info('Found existing record by email: ' . $data['email']);
                    }
                }
                
                // If not found by email, try to find by phone
                if (!$existingRecord && !empty($data['no_tlp'])) {
                    $existingRecord = Customer::where('no_tlp', $data['no_tlp'])->first();
                    if ($existingRecord) {
                        Log::info('Found existing record by phone: ' . $data['no_tlp']);
                    }
                }
                
                // Update existing record or create new one
                if ($existingRecord) {
                    Log::info('Updating existing customer #' . $existingRecord->customer_id);
                    $existingRecord->fill($data);
                    $existingRecord->save();
                    $this->updated++;
                } else {
                    Log::info('Creating new customer: ' . $data['nama']);
                    
                    // Create using direct insert to avoid any model events that might interfere
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
                    
                    Log::info('Created new customer with ID: ' . $customerId);
                    $this->inserted++;
                }
                
                $this->processed++;
                
            } catch (\Exception $e) {
                Log::error('Error processing row #' . ($index + 1) . ': ' . $e->getMessage());
                Log::error('Error stack trace: ' . $e->getTraceAsString());
                $this->errors[] = 'Row #' . ($index + 1) . ': ' . $e->getMessage();
            }
        }
        
        // Re-enable foreign key checks if you disabled them
        // DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        // Log summary
        Log::info('Import finished. Processed: ' . $this->processed . 
                 ', Inserted: ' . $this->inserted . 
                 ', Updated: ' . $this->updated . 
                 ', Skipped: ' . $this->skipped . 
                 ', Errors: ' . count($this->errors));
    }
    
    /**
     * Normalize row data
     */
    private function normalizeRowData($row)
    {
        // Convert row to array
        $rowArray = $row->toArray();
        
        // Normalize keys to lowercase
        $normalizedRow = [];
        foreach ($rowArray as $key => $value) {
            $normalizedRow[strtolower(trim($key))] = $value;
        }
        
        // Extract data with fallbacks
        return [
            'nama' => $normalizedRow['nama'] ?? null,
            'gender' => isset($normalizedRow['gender']) ? strtoupper(substr($normalizedRow['gender'], 0, 1)) : null,
            'usia' => $normalizedRow['usia'] ?? null,
            'alamat' => $normalizedRow['alamat'] ?? null,
            'email' => $normalizedRow['email'] ?? null,
            'no_tlp' => $normalizedRow['no_tlp'] ?? $normalizedRow['telepon'] ?? $normalizedRow['no_telepon'] ?? null,
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
     * Get the count of skipped rows
     */
    public function getSkippedCount()
    {
        return $this->skipped;
    }
    
    /**
     * Get the errors that occurred during import
     */
    public function getErrors()
    {
        return $this->errors;
    }
}