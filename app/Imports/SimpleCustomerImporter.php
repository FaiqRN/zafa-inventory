<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SimpleCustomerImporter implements ToCollection, WithHeadingRow
{
    protected int $processedCount = 0;
    protected int $insertedCount = 0;
    protected int $updatedCount = 0;

    /** @var array<int, string> */
    protected array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowData = $row instanceof Collection ? $row->toArray() : (array) $row;
            $this->processRowArray($rowData, ((int) $index) + 2);
        }
    }

    public function processRowArray(array $row, int $rowNumber): void
    {
        $normalizedRow = $this->normalizeRow($row);

        if ($this->isEmptyRow($normalizedRow)) {
            return;
        }

        $this->processedCount++;

        try {
            $customerData = $this->buildCustomerData($normalizedRow, $rowNumber);
            $this->upsertCustomer($customerData, $rowNumber);
        } catch (\Throwable $e) {
            $this->errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
        }
    }

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    public function getInsertedCount(): int
    {
        return $this->insertedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    /** @return array<int, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function resetImportState(): void
    {
        $this->processedCount = 0;
        $this->insertedCount = 0;
        $this->updatedCount = 0;
        $this->errors = [];
    }

    protected function normalizeRow(array $row): array
    {
        $normalized = [
            'nama' => null,
            'gender' => null,
            'usia' => null,
            'alamat' => null,
            'email' => null,
            'no_tlp' => null,
        ];

        foreach ($row as $key => $value) {
            $normalizedKey = $this->normalizeHeaderToField($this->normalizeKey((string) $key));

            if ($normalizedKey === null) {
                continue;
            }

            if ($normalized[$normalizedKey] === null || $normalized[$normalizedKey] === '') {
                $normalized[$normalizedKey] = $value;
            }
        }

        return $normalized;
    }

    protected function normalizeHeaderToField(string $header): ?string
    {
        $headerMap = [
            'nama' => 'nama',
            'name' => 'nama',
            'customer' => 'nama',
            'customer_name' => 'nama',
            'gender' => 'gender',
            'jenis_kelamin' => 'gender',
            'sex' => 'gender',
            'usia' => 'usia',
            'umur' => 'usia',
            'age' => 'usia',
            'alamat' => 'alamat',
            'address' => 'alamat',
            'email' => 'email',
            'no_tlp' => 'no_tlp',
            'no_telp' => 'no_tlp',
            'no_telpon' => 'no_tlp',
            'no_telepon' => 'no_tlp',
            'nomor_telepon' => 'no_tlp',
            'telepon' => 'no_tlp',
            'phone' => 'no_tlp',
            'no_hp' => 'no_tlp',
            'hp' => 'no_tlp',
        ];

        return $headerMap[$header] ?? null;
    }

    protected function normalizeKey(string $key): string
    {
        $key = trim($key);
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key) ?? $key;
        $key = strtolower($key);
        $key = str_replace([' ', '-', '.'], '_', $key);

        return preg_replace('/[^a-z0-9_]/', '', $key) ?? '';
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->nullableString($value) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, int|string|null>
     */
    protected function buildCustomerData(array $row, int $rowNumber): array
    {
        return [
            'nama' => $this->nullableString($row['nama'] ?? null),
            'gender' => $this->normalizeGender($row['gender'] ?? null, $rowNumber),
            'usia' => $this->normalizeUsia($row['usia'] ?? null, $rowNumber),
            'alamat' => $this->nullableString($row['alamat'] ?? null),
            'email' => $this->normalizeEmail($row['email'] ?? null, $rowNumber),
            'no_tlp' => $this->normalizePhone($row['no_tlp'] ?? null, $rowNumber),
        ];
    }

    /**
     * @param array<string, int|string|null> $data
     */
    protected function upsertCustomer(array $data, int $rowNumber): void
    {
        $customerByEmail = null;
        $customerByPhone = null;

        if ($data['email'] !== null) {
            $customerByEmail = Customer::query()->where('email', $data['email'])->first();
        }

        if ($data['no_tlp'] !== null) {
            $customerByPhone = Customer::query()->where('no_tlp', $data['no_tlp'])->first();
        }

        if ($customerByEmail && $customerByPhone && $customerByEmail->customer_id !== $customerByPhone->customer_id) {
            throw new \RuntimeException('email dan no_tlp merujuk ke customer yang berbeda.');
        }

        $customer = $customerByEmail ?: $customerByPhone;

        if ($customer) {
            $this->ensureUniqueValueForOtherCustomer('email', $data['email'], (int) $customer->customer_id, $rowNumber);
            $this->ensureUniqueValueForOtherCustomer('no_tlp', $data['no_tlp'], (int) $customer->customer_id, $rowNumber);

            $updateData = [];
            foreach (['nama', 'gender', 'usia', 'alamat', 'email', 'no_tlp'] as $field) {
                if ($data[$field] !== null) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (!empty($updateData)) {
                $customer->fill($updateData);
                if ($customer->isDirty()) {
                    $customer->save();
                }
            }

            $this->updatedCount++;
            return;
        }

        if ($data['nama'] === null || $data['alamat'] === null) {
            throw new \RuntimeException('nama dan alamat wajib diisi untuk data baru.');
        }

        Customer::create([
            'nama' => $data['nama'],
            'gender' => $data['gender'],
            'usia' => $data['usia'],
            'alamat' => $data['alamat'],
            'email' => $data['email'],
            'no_tlp' => $data['no_tlp'],
        ]);

        $this->insertedCount++;
    }

    protected function ensureUniqueValueForOtherCustomer(string $field, ?string $value, int $customerId, int $rowNumber): void
    {
        if ($value === null) {
            return;
        }

        $isTaken = Customer::query()
            ->where($field, $value)
            ->where('customer_id', '!=', $customerId)
            ->exists();

        if ($isTaken) {
            throw new \RuntimeException("{$field} sudah digunakan customer lain pada baris {$rowNumber}.");
        }
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    protected function normalizeGender(mixed $value, int $rowNumber): ?string
    {
        $gender = $this->nullableString($value);
        if ($gender === null) {
            return null;
        }

        $normalized = strtolower($gender);
        $maleValues = ['l', 'laki', 'laki_laki', 'laki-laki', 'male', 'm'];
        $femaleValues = ['p', 'perempuan', 'female', 'f'];

        if (in_array($normalized, $maleValues, true)) {
            return 'L';
        }

        if (in_array($normalized, $femaleValues, true)) {
            return 'P';
        }

        throw new \RuntimeException("nilai gender tidak valid pada baris {$rowNumber}.");
    }

    protected function normalizeUsia(mixed $value, int $rowNumber): ?int
    {
        $usia = $this->nullableString($value);
        if ($usia === null) {
            return null;
        }

        if (!is_numeric($usia)) {
            throw new \RuntimeException("nilai usia harus angka pada baris {$rowNumber}.");
        }

        $parsedUsia = (int) $usia;
        if ($parsedUsia < 0) {
            throw new \RuntimeException("nilai usia tidak boleh negatif pada baris {$rowNumber}.");
        }

        return $parsedUsia;
    }

    protected function normalizeEmail(mixed $value, int $rowNumber): ?string
    {
        $email = $this->nullableString($value);
        if ($email === null) {
            return null;
        }

        $email = strtolower($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException("format email tidak valid pada baris {$rowNumber}.");
        }

        if (strlen($email) > 100) {
            throw new \RuntimeException("email melebihi panjang maksimum pada baris {$rowNumber}.");
        }

        return $email;
    }

    protected function normalizePhone(mixed $value, int $rowNumber): ?string
    {
        $phone = $this->nullableString($value);
        if ($phone === null) {
            return null;
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone) ?? '';
        if ($phone === '') {
            return null;
        }

        if (strlen($phone) > 20) {
            throw new \RuntimeException("nomor telepon melebihi panjang maksimum pada baris {$rowNumber}.");
        }

        return $phone;
    }
}
