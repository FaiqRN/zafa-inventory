<?php

namespace App\Imports;

class ManualCSVImporter extends SimpleCustomerImporter
{
    /**
     * @return array{processed:int, inserted:int, updated:int}
     */
    public function import(string $filePath): array
    {
        $this->resetImportState();

        if (!is_readable($filePath)) {
            throw new \RuntimeException('File CSV tidak dapat dibaca.');
        }

        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Gagal membuka file CSV.');
        }

        try {
            $firstRow = fgetcsv($handle);
            if ($firstRow === false) {
                return [
                    'processed' => 0,
                    'inserted' => 0,
                    'updated' => 0,
                ];
            }

            $normalizedHeaders = array_map(function ($header) {
                return $this->normalizeHeaderToField($this->normalizeKey((string) $header));
            }, $firstRow);

            $hasHeaderRow = $this->containsKnownHeader($normalizedHeaders);
            $defaultHeaders = ['nama', 'gender', 'usia', 'alamat', 'email', 'no_tlp'];
            $rowNumber = 1;

            if (!$hasHeaderRow) {
                $firstData = $this->combineRowWithHeaders($defaultHeaders, $firstRow);
                $this->processRowArray($firstData, $rowNumber);
            }

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $headers = $hasHeaderRow ? $normalizedHeaders : $defaultHeaders;
                $rowData = $this->combineRowWithHeaders($headers, $row);
                $this->processRowArray($rowData, $rowNumber);
            }
        } finally {
            fclose($handle);
        }

        return [
            'processed' => $this->getProcessedCount(),
            'inserted' => $this->getInsertedCount(),
            'updated' => $this->getUpdatedCount(),
        ];
    }

    /**
     * @param array<int, string|null> $headers
     */
    protected function containsKnownHeader(array $headers): bool
    {
        foreach ($headers as $header) {
            if (in_array($header, ['nama', 'gender', 'usia', 'alamat', 'email', 'no_tlp'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string|null> $headers
     * @param array<int, mixed> $row
     * @return array<string, mixed>
     */
    protected function combineRowWithHeaders(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            if ($header === null || $header === '') {
                continue;
            }

            $mapped[$header] = $row[$index] ?? null;
        }

        return $mapped;
    }
}
