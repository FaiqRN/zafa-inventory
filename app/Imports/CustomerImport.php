<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;

class CustomersImport implements 
    ToModel, 
    WithHeadingRow, 
    WithValidation, 
    SkipsOnError, 
    SkipsOnFailure,
    WithBatchInserts,
    WithChunkReading
{
    use SkipsErrors, SkipsFailures;
    
    private $rowCount = 0;
    private $updatedCount = 0;
    private $duplicateCount = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Fix key names (make them lowercase)
        $row = array_change_key_case($row, CASE_LOWER);
        
        // Log the row for debugging
        Log::info('Processing import row: ' . json_encode($row));
        
        // Map column names from Excel to database fields
        $data = [
            'nama' => $row['nama'] ?? null,
            'usia' => $row['usia'] ?? null,
            'gender' => strtoupper(substr($row['gender'] ?? '', 0, 1)),
            'alamat' => $row['alamat'] ?? null,
            'email' => $row['email'] ?? null,
            'no_tlp' => $row['no_tlp'] ?? $row['telepon'] ?? $row['no_telepon'] ?? $row['no_hp'] ?? null,
        ];
        
        // If empty data, skip
        if (empty($data['nama']) && empty($data['email']) && empty($data['no_tlp'])) {
            Log::info('Skipping empty row');
            return null;
        }
        
        // Check for existing record by email
        if (!empty($data['email'])) {
            $existingCustomer = Customer::where('email', $data['email'])->first();
            
            if ($existingCustomer) {
                Log::info('Updating existing customer by email: ' . $data['email']);
                $existingCustomer->update($data);
                $this->updatedCount++;
                return null;
            }
        }
        
        // Check for existing record by phone
        if (!empty($data['no_tlp'])) {
            $existingCustomer = Customer::where('no_tlp', $data['no_tlp'])->first();
            
            if ($existingCustomer) {
                Log::info('Updating existing customer by phone: ' . $data['no_tlp']);
                $existingCustomer->update($data);
                $this->updatedCount++;
                return null;
            }
        }
        
        // Create new record
        $this->rowCount++;
        Log::info('Creating new customer: ' . json_encode($data));
        
        return new Customer($data);
    }
    
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            '*.nama' => 'required|string|max:100',
            '*.usia' => 'nullable|integer|min:0',
            '*.gender' => 'nullable|string',
            '*.alamat' => 'nullable|string',
            '*.email' => 'nullable|email|max:100',
            '*.no_tlp' => 'nullable|string|max:20',
            '*.telepon' => 'nullable|string|max:20',
            '*.no_telepon' => 'nullable|string|max:20',
            '*.no_hp' => 'nullable|string|max:20',
        ];
    }
    
    public function getRowCount(): int
    {
        return $this->rowCount;
    }
    
    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }
    
    public function getDuplicateCount(): int
    {
        return $this->duplicateCount;
    }
    
    public function batchSize(): int
    {
        return 100;
    }
    
    public function chunkSize(): int
    {
        return 100;
    }
}