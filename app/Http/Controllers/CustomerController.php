<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Pemesanan;
use Illuminate\Http\Request;
use App\Imports\ManualCSVImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\SimpleCustomerImporter;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     */
    public function index()
    {
        // Log jumlah customer untuk debugging
        $totalCustomers = Customer::count();
        $customersFromPemesanan = Customer::whereNotNull('pemesanan_id')->count();
        Log::info("Total customers: $totalCustomers, From pemesanan: $customersFromPemesanan");
        
        return view('customer.index', [
            'activemenu' => 'customer',
            'breadcrumb' => (object) [
                'title' => 'Data Customer',
                'list' => ['Home', 'Master Data', 'Data Customer']
            ]
        ]);
    }

    /**
     * Get customer data for DataTables
     */
    public function getData(Request $request)
    {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length");

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');
        $search_arr = $request->get('search');

        $columnIndex = $columnIndex_arr[0]['column'];
        $columnName = $columnName_arr[$columnIndex]['data'];
        $columnSortOrder = $order_arr[0]['dir'];
        $searchValue = $search_arr['value'];

        // Total records
        $totalRecords = Customer::count();
        $totalRecordswithFilter = Customer::search($searchValue)->count();

        // Fetch records
        $records = Customer::search($searchValue)
            ->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($rowperpage)
            ->get();

        $data_arr = [];
        $i = $start + 1;
        
        foreach ($records as $record) {
            $data_arr[] = [
                "no" => $i++,
                "customer_id" => $record->customer_id,
                "nama" => $record->nama,
                "gender" => $record->gender == 'L' ? 'Laki-laki' : 'Perempuan',
                "usia" => $record->usia,
                "alamat" => $record->alamat,
                "email" => $record->email,
                "no_tlp" => $record->no_tlp,
                "source" => $record->getSourceLabel(),
                "created_at" => $record->created_at->format('d-m-Y H:i:s'),
                "actions" => ''
            ];
        }

        $response = [
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $data_arr
        ];

        return response()->json($response);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'gender' => 'nullable|in:L,P',
            'usia' => 'nullable|integer|min:0',
            'alamat' => 'required|string',
            'email' => 'nullable|email|max:100|unique:data_customer,email',
            'no_tlp' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $customer = Customer::create($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data customer berhasil ditambahkan',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating customer: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan data customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific customer for editing.
     */
    public function edit($id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data customer tidak ditemukan'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $customer
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data customer tidak ditemukan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'gender' => 'nullable|in:L,P',
            'usia' => 'nullable|integer|min:0',
            'alamat' => 'required|string',
            'email' => 'nullable|email|max:100|unique:data_customer,email,' . $id . ',customer_id',
            'no_tlp' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $customer->update($request->all());
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data customer berhasil diperbarui',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating customer: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memperbarui data customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified customer.
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);
        
        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data customer tidak ditemukan'
            ], 404);
        }

        try {
            $customer->delete();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Data customer berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting customer: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data customer: ' . $e->getMessage()
            ], 500);
        }
    }

// Tambahkan fungsi import ini ke CustomerController.php

/**
 * Import customers from Excel/CSV file
 */
public function import(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file' => 'required|file|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first()
        ], 422);
    }

    try {
        // Get file details
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        
        // Log file information
        Log::info('Import file details:', [
            'name' => $originalName,
            'extension' => $extension,
            'mimeType' => $mimeType,
            'size' => $file->getSize()
        ]);
        
        // Store file temporarily
        $tempPath = $file->getRealPath();
        $storedPath = storage_path('app/temp_imports/') . time() . '_' . $originalName;
        
        // Create directory if it doesn't exist
        if (!file_exists(storage_path('app/temp_imports/'))) {
            mkdir(storage_path('app/temp_imports/'), 0755, true);
        }
        
        // Copy file to storage
        copy($tempPath, $storedPath);
        Log::info('File stored at: ' . $storedPath);
        
        // Use different import methods based on file type
        if ($extension == 'csv' || $mimeType == 'text/csv' || $mimeType == 'text/plain') {
            // Use manual CSV importer
            $importer = new ManualCSVImporter();
            $result = $importer->import($storedPath);
            
            $processed = $result['processed'];
            $inserted = $result['inserted'];
            $updated = $result['updated'];
            $errors = $importer->getErrors();
        } else {
            // Use Excel importer for xlsx/xls
            $importer = new SimpleCustomerImporter();
            Excel::import($importer, $storedPath);
            
            $processed = $importer->getProcessedCount();
            $inserted = $importer->getInsertedCount();
            $updated = $importer->getUpdatedCount();
            $errors = $importer->getErrors();
        }
        
        // Log import results
        Log::info('Import completed:', [
            'processed' => $processed,
            'inserted' => $inserted,
            'updated' => $updated,
            'errors' => count($errors ?? [])
        ]);
        
        // Clean up temporary file
        if (file_exists($storedPath)) {
            unlink($storedPath);
        }
        
        // Build response message
        $message = "Berhasil mengimpor {$inserted} data baru";
        if ($updated > 0) {
            $message .= " dan memperbarui {$updated} data yang sudah ada";
        }
        
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'details' => [
                'processed' => $processed,
                'inserted' => $inserted,
                'updated' => $updated,
                'errors' => $errors ?? []
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Import error: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'status' => 'error',
            'message' => 'Gagal mengimpor data: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Sync customers from pemesanan data - FIXED VERSION
     */
    public function syncFromPemesanan()
    {
        try {
            // Cek dulu koneksi ke tabel pemesanan
            $totalPemesanan = DB::table('pemesanan')->count();
            
            // Log untuk debugging
            Log::info('Total pemesanan: ' . $totalPemesanan);
            
            // Get customers from pemesanan who don't exist in customer table yet
            $newCustomers = DB::table('pemesanan as p')
                ->select(
                    'p.pemesanan_id',
                    'p.nama_pemesan as nama',
                    'p.alamat_pemesan as alamat',
                    'p.email_pemesan as email',
                    'p.no_telp_pemesan as no_tlp',
                    DB::raw('NOW() as created_at'),
                    DB::raw('NOW() as updated_at')
                )
                ->whereNotNull('p.nama_pemesan')
                ->whereRaw('p.pemesanan_id NOT IN (SELECT pemesanan_id FROM data_customer WHERE pemesanan_id IS NOT NULL)')
                ->get();

            Log::info('New customers found: ' . $newCustomers->count());

            if ($newCustomers->isEmpty()) {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Tidak ada data pemesanan baru untuk disinkronkan.'
                ]);
            }

            // Insert new customers
            $inserted = 0;
            foreach ($newCustomers as $customer) {
                $customerData = (array) $customer;
                
                // Skip if email exists to prevent duplicate entries
                if (!empty($customer->email) && Customer::emailExists($customer->email)) {
                    continue;
                }
                
                // Debug log
                Log::info('Inserting customer: ' . json_encode($customerData));

                Customer::create($customerData);
                $inserted++;
            }
            
            Log::info('Successfully inserted ' . $inserted . ' new customers from pemesanan');

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menyinkronkan ' . $inserted . ' data customer baru dari pemesanan',
                'count' => $inserted
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error syncing data: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyinkronkan data: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Debug database tables structure
     */
    public function debugTables()
    {
        try {
            // Periksa struktur tabel pemesanan
            $pemesananColumns = DB::getSchemaBuilder()->getColumnListing('pemesanan');
            
            // Periksa data pemesanan (ambil 5 record pertama)
            $pemesananSamples = DB::table('pemesanan')->take(5)->get();
            
            // Periksa struktur tabel customer
            $customerColumns = DB::getSchemaBuilder()->getColumnListing('data_customer');
            
            // Periksa data customer (ambil 5 record pertama)
            $customerSamples = DB::table('data_customer')->take(5)->get();
            
            return response()->json([
                'pemesanan_columns' => $pemesananColumns,
                'pemesanan_samples' => $pemesananSamples,
                'customer_columns' => $customerColumns,
                'customer_samples' => $customerSamples
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}