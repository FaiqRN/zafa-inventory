<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Pemesanan;
use Illuminate\Http\Request;
use App\Imports\ManualCSVImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Imports\SimpleCustomerImporter;
use Illuminate\Support\Facades\Validator;
use App\Helpers\DashboardMonitorLogger;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view-customer')->only([
            'index',
            'getData',
        ]);
        $this->middleware('can:create-customer')->only([
            'store',
            'import',
            'syncFromPemesanan',
        ]);
        $this->middleware('can:edit-customer')->only([
            'edit',
            'update',
        ]);
        $this->middleware('can:delete-customer')->only(['destroy']);
    }

    public function index()
    {
        return view('Customer.index', [
            'activemenu' => 'customer',
            'breadcrumb' => (object) [
                'title' => 'Data Customer',
                'list' => ['Home', 'Master Data', 'Data Customer']
            ]
        ]);
    }

    public function getData(Request $request)
    {
        try {
            $draw = $request->get('draw', 1);
            $start = $request->get('start', 0);
            $rowperpage = $request->get('length', 10);
            $search_arr = $request->get('search', []);
            $order_arr = $request->get('order', []);
            $columns_arr = $request->get('columns', []);

            // Validasi dan sanitasi sort column
            $columnName = 'customer_id';
            $columnSortOrder = 'desc';
            
            if (!empty($order_arr) && is_array($order_arr)) {
                $columnIndex = $order_arr[0]['column'] ?? 0;
                if (isset($columns_arr[$columnIndex]['data'])) {
                    $requestedColumn = $columns_arr[$columnIndex]['data'];
                    // Whitelist columns untuk prevent SQL injection
                    $allowedColumns = ['customer_id', 'nama', 'gender', 'usia', 'alamat', 'email', 'no_tlp', 'created_at'];
                    if (in_array($requestedColumn, $allowedColumns)) {
                        $columnName = $requestedColumn;
                    }
                }
                $columnSortOrder = in_array(strtoupper($order_arr[0]['dir'] ?? ''), ['ASC', 'DESC']) ? $order_arr[0]['dir'] : 'desc';
            }
            
            $searchValue = $search_arr['value'] ?? '';

            $totalRecords = Customer::count();
            $totalRecordswithFilter = Customer::search($searchValue)->count();

            $records = Customer::search($searchValue)
                ->orderBy($columnName, $columnSortOrder)
                ->skip($start)
                ->take($rowperpage)
                ->get();

            $data_arr = [];
            $i = $start + 1;
            
            foreach ($records as $record) {
                $data_arr[] = [
                    'no' => $i++,
                    'customer_id' => $record->customer_id,
                    'nama' => $record->nama,
                    'gender' => $record->gender == 'L' ? 'Laki-laki' : 'Perempuan',
                    'usia' => $record->usia,
                    'alamat' => $record->alamat,
                    'email' => $record->email,
                    'no_tlp' => $record->no_tlp,
                    'source' => $record->getSourceLabel(),
                    'created_at' => $record->created_at->format('d-m-Y H:i:s'),
                    'actions' => ''
                ];
            }

            return response()->json([
                'draw' => intval($draw),
                'iTotalRecords' => $totalRecords,
                'iTotalDisplayRecords' => $totalRecordswithFilter,
                'aaData' => $data_arr
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching customer data: ' . $e->getMessage());
            return response()->json([
                'draw' => intval($request->get('draw', 1)),
                'iTotalRecords' => 0,
                'iTotalDisplayRecords' => 0,
                'aaData' => []
            ]);
        }
    }

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

            DashboardMonitorLogger::create('Customer', "Tambah customer {$customer->nama}", $customer->toArray(), $request);
            
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
            $oldData = $customer->toArray();
            $customer->update($request->all());

            DashboardMonitorLogger::update('Customer', "Ubah customer {$customer->nama}", $oldData, $customer->toArray(), $request);
            
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
            DashboardMonitorLogger::delete('Customer', "Hapus customer {$customer->nama}", $customer->toArray());

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

    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt,xls,xlsx|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ], 422);
        }

        $storedPath = null;
        
        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
            
            $tempPath = $file->getRealPath();
            $tempDir = storage_path('app/temp_imports');
            
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            $storedPath = $tempDir . '/' . time() . '_' . $originalName;
            
            if (!copy($tempPath, $storedPath)) {
                throw new \Exception('Gagal menyalin file ke direktori temporary');
            }
            
            // Process berdasarkan extension
            if ($extension === 'csv' || $mimeType === 'text/csv' || $mimeType === 'text/plain') {
                $importer = new ManualCSVImporter();
                $result = $importer->import($storedPath);
                $processed = $result['processed'] ?? 0;
                $inserted = $result['inserted'] ?? 0;
                $updated = $result['updated'] ?? 0;
                $errors = $importer->getErrors();
            } else {
                $importer = new SimpleCustomerImporter();
                $result = $importer->import($storedPath);
                $processed = $result['processed'] ?? 0;
                $inserted = $result['inserted'] ?? 0;
                $updated = $result['updated'] ?? 0;
                $errors = $importer->getErrors();
            }

            $message = "Berhasil mengimpor {$inserted} data baru";
            if ($updated > 0) {
                $message .= " dan memperbarui {$updated} data yang sudah ada";
            }

            DashboardMonitorLogger::create('Customer', "Import customer: {$inserted} baru, {$updated} diperbarui", ['processed' => $processed, 'inserted' => $inserted, 'updated' => $updated], $request);
            
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
        } finally {
            // Guarantee file cleanup
            if ($storedPath && file_exists($storedPath)) {
                unlink($storedPath);
            }
        }
    }

    public function syncFromPemesanan()
    {
        try {
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

            if ($newCustomers->isEmpty()) {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Tidak ada data pemesanan baru untuk disinkronkan.'
                ]);
            }

            $inserted = 0;
            foreach ($newCustomers as $customer) {
                $customerData = (array) $customer;
                
                // Skip if email exists to prevent duplicate entries
                if (!empty($customer->email) && Customer::emailExists($customer->email)) {
                    continue;
                }

                Customer::create($customerData);
                $inserted++;
            }

            DashboardMonitorLogger::create('Customer', "Sinkronisasi {$inserted} customer dari pemesanan", ['inserted' => $inserted]);

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
}

