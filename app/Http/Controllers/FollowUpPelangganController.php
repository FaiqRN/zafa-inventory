<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\FollowUp;
use App\Models\Pemesanan;
use App\Models\Customer;
use App\Services\WablasService;
use Carbon\Carbon;

class FollowUpPelangganController extends Controller
{
    protected $wablasService;

    public function __construct(WablasService $wablasService)
    {
        $this->wablasService = $wablasService;
    }

    /**
     * Display the follow up pelanggan page
     */
    public function index()
    {
        $breadcrumb = (object) [
            'title' => 'Follow Up Pelanggan',
            'list' => ['Home', 'Follow Up Pelanggan']
        ];

        $page = (object) [
            'title' => 'Follow Up Pelanggan - Zafa Potato CRM'
        ];

        $activemenu = 'follow-up-pelanggan';

        return view('follow-up.index', [
            'breadcrumb' => $breadcrumb,
            'page' => $page,
            'activemenu' => $activemenu
        ]);
    }

    /**
     * Get filtered customers data
     */
    public function getFilteredCustomers(Request $request)
    {
        $filters = $request->get('filters', []);
        $search = $request->get('search');
        
        if (empty($filters)) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }

        $customers = collect();

        foreach ($filters as $filter) {
            switch ($filter) {
                case 'pelangganLama':
                    $customers = $customers->merge($this->getPelangganLama());
                    break;
                case 'pelangganBaru':
                    $customers = $customers->merge($this->getPelangganBaru());
                    break;
                case 'pelangganTidakKembali':
                    $customers = $customers->merge($this->getPelangganTidakKembali());
                    break;
                case 'keseluruhan':
                    $customers = $customers->merge($this->getKeseluruhanPelanggan());
                    break;
                case 'shopee':
                case 'tokopedia':
                case 'whatsapp':
                case 'instagram':
                case 'langsung':
                    $customers = $customers->merge($this->getPelangganBySource($filter));
                    break;
            }
        }

        // Remove duplicates based on phone number
        $customers = $customers->unique('phone');

        // Apply search filter
        if ($search) {
            $customers = $customers->filter(function ($customer) use ($search) {
                return stripos($customer['name'], $search) !== false ||
                       stripos($customer['phone'], $search) !== false ||
                       stripos($customer['email'], $search) !== false;
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $customers->values()->all()
        ]);
    }

    /**
     * Get Pelanggan Lama (>3 transaksi)
     * Berdasarkan kesamaan nama_pemesanan, no_tlp_pemesan, email_pemesan
     */
    private function getPelangganLama()
    {
        // Ambil data dengan GROUP BY untuk mencari yang memiliki transaksi >3
        $pelangganLama = DB::table('pemesanan')
            ->select([
                'nama_pemesan as name',
                'no_telp_pemesan as phone',
                'email_pemesan as email',
                'alamat_pemesan as address',
                'pemesanan_dari as orderSource',
                DB::raw('COUNT(*) as totalOrders'),
                DB::raw('SUM(total) as totalSpent'),
                DB::raw('MAX(tanggal_pemesanan) as lastOrder'),
                DB::raw('MAX(pemesanan_id) as lastOrderId')
            ])
            ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan')
            ->havingRaw('COUNT(*) >= 3')
            ->get();

        return $pelangganLama->map(function ($customer) {
            // Get last product info
            $lastOrder = Pemesanan::where('pemesanan_id', $customer->lastOrderId)
                ->with('barang')
                ->first();

            return [
                'id' => 'lama_' . md5($customer->phone),
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'lastOrder' => Carbon::parse($customer->lastOrder)->format('Y-m-d'),
                'totalOrders' => $customer->totalOrders,
                'totalSpent' => 'Rp ' . number_format($customer->totalSpent, 0, ',', '.'),
                'customerType' => 'pelangganLama',
                'orderSource' => $customer->orderSource,
                'lastProduct' => $lastOrder ? $lastOrder->barang->nama_barang ?? 'Unknown Product' : 'Unknown Product',
                'notes' => 'Pelanggan setia dengan ' . $customer->totalOrders . ' transaksi',
                'initial' => $this->getCustomerInitial($customer->name)
            ];
        });
    }

    /**
     * Get Pelanggan Baru (1 bulan terakhir, hanya 1 kali transaksi)
     */
    private function getPelangganBaru()
    {
        $oneMonthAgo = Carbon::now()->subMonth();

        $pelangganBaru = DB::table('pemesanan')
            ->select([
                'nama_pemesan as name',
                'no_telp_pemesan as phone',
                'email_pemesan as email',
                'alamat_pemesan as address',
                'pemesanan_dari as orderSource',
                'tanggal_pemesanan as lastOrder',
                'total as totalSpent',
                'pemesanan_id as lastOrderId'
            ])
            ->where('tanggal_pemesanan', '>=', $oneMonthAgo)
            ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan')
            ->havingRaw('COUNT(*) = 1')
            ->get();

        return $pelangganBaru->map(function ($customer) {
            $lastOrder = Pemesanan::where('pemesanan_id', $customer->lastOrderId)
                ->with('barang')
                ->first();

            return [
                'id' => 'baru_' . md5($customer->phone),
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'lastOrder' => Carbon::parse($customer->lastOrder)->format('Y-m-d'),
                'totalOrders' => 1,
                'totalSpent' => 'Rp ' . number_format($customer->totalSpent, 0, ',', '.'),
                'customerType' => 'pelangganBaru',
                'orderSource' => $customer->orderSource,
                'lastProduct' => $lastOrder ? $lastOrder->barang->nama_barang ?? 'Unknown Product' : 'Unknown Product',
                'notes' => 'Pelanggan baru, bergabung dalam 1 bulan terakhir',
                'initial' => $this->getCustomerInitial($customer->name)
            ];
        });
    }

    /**
     * Get Pelanggan Tidak Kembali (>2 bulan tidak transaksi)
     */
    private function getPelangganTidakKembali()
    {
        $twoMonthsAgo = Carbon::now()->subMonths(2);

        $pelangganTidakKembali = DB::table('pemesanan')
            ->select([
                'nama_pemesan as name',
                'no_telp_pemesan as phone',
                'email_pemesan as email',
                'alamat_pemesan as address',
                'pemesanan_dari as orderSource',
                DB::raw('COUNT(*) as totalOrders'),
                DB::raw('SUM(total) as totalSpent'),
                DB::raw('MAX(tanggal_pemesanan) as lastOrder'),
                DB::raw('MAX(pemesanan_id) as lastOrderId')
            ])
            ->where('tanggal_pemesanan', '<', $twoMonthsAgo)
            ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan')
            ->get();

        return $pelangganTidakKembali->map(function ($customer) {
            $lastOrder = Pemesanan::where('pemesanan_id', $customer->lastOrderId)
                ->with('barang')
                ->first();

            return [
                'id' => 'tidak_kembali_' . md5($customer->phone),
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'lastOrder' => Carbon::parse($customer->lastOrder)->format('Y-m-d'),
                'totalOrders' => $customer->totalOrders,
                'totalSpent' => 'Rp ' . number_format($customer->totalSpent, 0, ',', '.'),
                'customerType' => 'pelangganTidakKembali',
                'orderSource' => $customer->orderSource,
                'lastProduct' => $lastOrder ? $lastOrder->barang->nama_barang ?? 'Unknown Product' : 'Unknown Product',
                'notes' => 'Tidak bertransaksi >2 bulan, perlu follow up',
                'initial' => $this->getCustomerInitial($customer->name)
            ];
        });
    }

    /**
     * Get Keseluruhan Pelanggan (dari pemesanan dan data_customer)
     */
    private function getKeseluruhanPelanggan()
    {
        // Dari table pemesanan
        $fromPemesanan = DB::table('pemesanan')
            ->select([
                'nama_pemesan as name',
                'no_telp_pemesan as phone',
                'email_pemesan as email',
                'alamat_pemesan as address',
                'pemesanan_dari as orderSource',
                DB::raw('COUNT(*) as totalOrders'),
                DB::raw('SUM(total) as totalSpent'),
                DB::raw('MAX(tanggal_pemesanan) as lastOrder'),
                DB::raw('MAX(pemesanan_id) as lastOrderId'),
                DB::raw("'pemesanan' as source_table")
            ])
            ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan', 'alamat_pemesan', 'pemesanan_dari')
            ->get();

        // Dari table data_customer
        $fromCustomer = DB::table('data_customer')
            ->leftJoin('pemesanan', 'data_customer.pemesanan_id', '=', 'pemesanan.pemesanan_id')
            ->select([
                'data_customer.nama as name',
                'data_customer.no_tlp as phone',
                'data_customer.email as email',
                'data_customer.alamat as address',
                DB::raw('COALESCE(pemesanan.pemesanan_dari, "manual") as orderSource'),
                DB::raw('CASE WHEN pemesanan.pemesanan_id IS NOT NULL THEN 1 ELSE 0 END as totalOrders'),
                DB::raw('COALESCE(pemesanan.total, 0) as totalSpent'),
                DB::raw('COALESCE(pemesanan.tanggal_pemesanan, data_customer.created_at) as lastOrder'),
                'pemesanan.pemesanan_id as lastOrderId',
                DB::raw("'customer' as source_table")
            ])
            ->whereNotNull('data_customer.nama')
            ->get();

        $allCustomers = $fromPemesanan->merge($fromCustomer);

        return $allCustomers->map(function ($customer) {
            $lastOrder = null;
            if ($customer->lastOrderId) {
                $lastOrder = Pemesanan::where('pemesanan_id', $customer->lastOrderId)
                    ->with('barang')
                    ->first();
            }

            return [
                'id' => 'all_' . md5($customer->phone . $customer->source_table),
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'lastOrder' => $customer->lastOrder ? Carbon::parse($customer->lastOrder)->format('Y-m-d') : '-',
                'totalOrders' => $customer->totalOrders,
                'totalSpent' => 'Rp ' . number_format($customer->totalSpent, 0, ',', '.'),
                'customerType' => 'keseluruhan',
                'orderSource' => $customer->orderSource,
                'lastProduct' => $lastOrder ? $lastOrder->barang->nama_barang ?? 'Unknown Product' : 'No Purchase',
                'notes' => 'Data ' . ($customer->source_table === 'pemesanan' ? 'dari transaksi' : 'customer manual'),
                'initial' => $this->getCustomerInitial($customer->name)
            ];
        });
    }

    /**
     * Get customers by order source
     */
    private function getPelangganBySource($source)
    {
        $customers = DB::table('pemesanan')
            ->select([
                'nama_pemesan as name',
                'no_telp_pemesan as phone',
                'email_pemesan as email',
                'alamat_pemesan as address',
                'pemesanan_dari as orderSource',
                DB::raw('COUNT(*) as totalOrders'),
                DB::raw('SUM(total) as totalSpent'),
                DB::raw('MAX(tanggal_pemesanan) as lastOrder'),
                DB::raw('MAX(pemesanan_id) as lastOrderId')
            ])
            ->where('pemesanan_dari', $source)
            ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan')
            ->get();

        return $customers->map(function ($customer) {
            $lastOrder = Pemesanan::where('pemesanan_id', $customer->lastOrderId)
                ->with('barang')
                ->first();

            return [
                'id' => $customer->orderSource . '_' . md5($customer->phone),
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'lastOrder' => Carbon::parse($customer->lastOrder)->format('Y-m-d'),
                'totalOrders' => $customer->totalOrders,
                'totalSpent' => 'Rp ' . number_format($customer->totalSpent, 0, ',', '.'),
                'customerType' => $this->determineCustomerType($customer->totalOrders, $customer->lastOrder),
                'orderSource' => $customer->orderSource,
                'lastProduct' => $lastOrder ? $lastOrder->barang->nama_barang ?? 'Unknown Product' : 'Unknown Product',
                'notes' => 'Customer dari ' . $customer->orderSource,
                'initial' => $this->getCustomerInitial($customer->name)
            ];
        });
    }

    /**
     * Send follow up message
     */
    public function sendFollowUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customers' => 'required|array|min:1',
            'customers.*' => 'required|array',
            'customers.*.phone' => 'required|string',
            'customers.*.name' => 'required|string',
            'message' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'target_type' => 'required|in:pelangganLama,pelangganBaru,pelangganTidakKembali,keseluruhan'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customers = $request->customers;
        $message = $request->message;
        $targetType = $request->target_type;
        $imagePaths = [];

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('follow-up-images', $imageName, 'public');
                $imagePaths[] = $imagePath;
            }
        }

        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($customers as $customer) {
            try {
                // Validate phone number
                if (!$this->wablasService->validatePhoneNumber($customer['phone'])) {
                    $results[] = [
                        'customer' => $customer['name'],
                        'phone' => $customer['phone'],
                        'status' => 'failed',
                        'error' => 'Invalid phone number format'
                    ];
                    $failedCount++;
                    continue;
                }

                // Create follow up record
                $followUp = FollowUp::create([
                    'customer_name' => $customer['name'],
                    'phone_number' => $customer['phone'],
                    'customer_email' => $customer['email'] ?? null,
                    'target_type' => $targetType,
                    'message' => $message,
                    'images' => $imagePaths,
                    'source_channel' => $customer['orderSource'] ?? null,
                    'status' => 'pending'
                ]);

                // Send message via WhatsApp
                if (!empty($message) && !empty($imagePaths)) {
                    // Send both message and images
                    $imageUrls = array_map(function($path) {
                        return asset('storage/' . $path);
                    }, $imagePaths);
                    
                    $wablasResult = $this->wablasService->sendMessageWithImages(
                        $customer['phone'],
                        $message,
                        $imageUrls
                    );
                } elseif (!empty($message)) {
                    // Send text only
                    $wablasResult = $this->wablasService->sendMessage($customer['phone'], $message);
                } elseif (!empty($imagePaths)) {
                    // Send images only
                    $imageUrls = array_map(function($path) {
                        return asset('storage/' . $path);
                    }, $imagePaths);
                    
                    $wablasResult = $this->wablasService->sendMultipleImages($customer['phone'], $imageUrls);
                } else {
                    throw new \Exception('No message or images to send');
                }

                // Update follow up record based on result
                if (isset($wablasResult['success']) && $wablasResult['success']) {
                    $followUp->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'wablas_message_id' => $wablasResult['message_id'] ?? null,
                        'wablas_response' => $wablasResult['response']
                    ]);
                    
                    $results[] = [
                        'customer' => $customer['name'],
                        'phone' => $customer['phone'],
                        'status' => 'success',
                        'message_id' => $wablasResult['message_id'] ?? null
                    ];
                    $successCount++;
                } else {
                    $followUp->update([
                        'status' => 'failed',
                        'error_message' => $wablasResult['error'] ?? 'Unknown error',
                        'wablas_response' => $wablasResult['response']
                    ]);
                    
                    $results[] = [
                        'customer' => $customer['name'],
                        'phone' => $customer['phone'],
                        'status' => 'failed',
                        'error' => $wablasResult['error'] ?? 'Unknown error'
                    ];
                    $failedCount++;
                }

            } catch (\Exception $e) {
                Log::error('Follow up send error: ' . $e->getMessage());
                
                $results[] = [
                    'customer' => $customer['name'],
                    'phone' => $customer['phone'],
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
                $failedCount++;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Follow up selesai. Berhasil: {$successCount}, Gagal: {$failedCount}",
            'summary' => [
                'total' => count($customers),
                'success' => $successCount,
                'failed' => $failedCount
            ],
            'results' => $results
        ]);
    }

    /**
     * Get follow up history
     */
    public function getHistory(Request $request)
    {
        $customerId = $request->get('customer_id');
        $targetType = $request->get('target_type');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = FollowUp::with(['pemesanan', 'customer']);

        // Apply filters
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($targetType) {
            $query->where('target_type', $targetType);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }

        $history = $query->orderBy('created_at', 'desc')->get();

        $formattedHistory = $history->map(function ($item) {
            return [
                'id' => $item->follow_up_id,
                'tanggal' => $item->created_at->format('Y-m-d H:i'),
                'customerName' => $item->customer_name,
                'phone' => $item->phone_number,
                'pesan' => $item->message ?: 'Pesan dengan gambar',
                'gambar' => !empty($item->images) ? asset('storage/' . $item->images[0]) : null,
                'targetType' => $item->target_type_label,
                'status' => $item->status_label,
                'sourceChannel' => $item->source_channel
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedHistory
        ]);
    }

    /**
     * Upload image for follow up
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'File tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('follow-up-images', $imageName, 'public');

            return response()->json([
                'status' => 'success',
                'message' => 'Gambar berhasil diupload',
                'data' => [
                    'path' => $imagePath,
                    'url' => asset('storage/' . $imagePath),
                    'name' => $imageName
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupload gambar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer initial for display
     */
    private function getCustomerInitial($name)
    {
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    /**
     * Determine customer type based on orders and date
     */
    private function determineCustomerType($totalOrders, $lastOrder)
    {
        $lastOrderDate = Carbon::parse($lastOrder);
        $oneMonthAgo = Carbon::now()->subMonth();
        $twoMonthsAgo = Carbon::now()->subMonths(2);

        if ($totalOrders >= 3) {
            return 'pelangganLama';
        } elseif ($totalOrders === 1 && $lastOrderDate->gte($oneMonthAgo)) {
            return 'pelangganBaru';
        } elseif ($lastOrderDate->lt($twoMonthsAgo)) {
            return 'pelangganTidakKembali';
        } else {
            return 'keseluruhan';
        }
    }

    /**
     * Send individual follow up to specific customer
     */
    public function sendIndividualFollowUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
            'customer_name' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'message' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $customerId = $request->customer_id;
            $customerName = $request->customer_name;
            $phone = $request->phone;
            $email = $request->email;
            $message = $request->message;
            $imagePaths = [];

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('follow-up-images', $imageName, 'public');
                    $imagePaths[] = $imagePath;
                }
            }

            // Validate phone number
            if (!$this->wablasService->validatePhoneNumber($phone)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Format nomor telepon tidak valid'
                ], 422);
            }

            // Create follow up record
            $followUp = FollowUp::create([
                'customer_name' => $customerName,
                'phone_number' => $phone,
                'customer_email' => $email,
                'target_type' => 'individual',
                'message' => $message,
                'images' => $imagePaths,
                'status' => 'pending'
            ]);

            // Send message via WhatsApp
            if (!empty($message) && !empty($imagePaths)) {
                // Send both message and images
                $imageUrls = array_map(function($path) {
                    return asset('storage/' . $path);
                }, $imagePaths);
                
                $wablasResult = $this->wablasService->sendMessageWithImages($phone, $message, $imageUrls);
            } elseif (!empty($message)) {
                // Send text only
                $wablasResult = $this->wablasService->sendMessage($phone, $message);
            } elseif (!empty($imagePaths)) {
                // Send images only
                $imageUrls = array_map(function($path) {
                    return asset('storage/' . $path);
                }, $imagePaths);
                
                $wablasResult = $this->wablasService->sendMultipleImages($phone, $imageUrls);
            } else {
                throw new \Exception('No message or images to send');
            }

            // Update follow up record based on result
            if (isset($wablasResult['success']) && $wablasResult['success']) {
                $followUp->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'wablas_message_id' => $wablasResult['message_id'] ?? null,
                    'wablas_response' => $wablasResult['response']
                ]);

                // Dispatch job to track message status
                if ($wablasResult['message_id']) {
                    \App\Jobs\UpdateMessageStatusJob::dispatch($followUp->follow_up_id)
                        ->delay(now()->addMinutes(2));
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Follow up berhasil dikirim!',
                    'data' => [
                        'follow_up_id' => $followUp->follow_up_id,
                        'message_id' => $wablasResult['message_id'] ?? null
                    ]
                ]);
            } else {
                $followUp->update([
                    'status' => 'failed',
                    'error_message' => $wablasResult['error'] ?? 'Unknown error',
                    'wablas_response' => $wablasResult['response']
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengirim follow up: ' . ($wablasResult['error'] ?? 'Unknown error')
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Individual follow up send error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get follow up status
     */
    public function getFollowUpStatus($id)
    {
        try {
            $followUp = FollowUp::find($id);
            
            if (!$followUp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Follow up tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $followUp->follow_up_id,
                    'customer_name' => $followUp->customer_name,
                    'phone_number' => $followUp->phone_number,
                    'message_status' => $followUp->status,
                    'sent_at' => $followUp->sent_at,
                    'delivered_at' => $followUp->delivered_at,
                    'read_at' => $followUp->read_at,
                    'wablas_message_id' => $followUp->wablas_message_id,
                    'error_message' => $followUp->error_message
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WhatsApp device status
     */
    public function getDeviceStatus()
    {
        try {
            $deviceStatus = $this->wablasService->getDeviceStatus();
            
            return response()->json([
                'status' => 'success',
                'data' => $deviceStatus
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengecek status device: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test WhatsApp connection
     */
    public function testWhatsAppConnection()
    {
        try {
            // Test dengan mengirim pesan ke nomor admin/test
            $testPhone = config('app.admin_phone', '6281234567890');
            $testMessage = 'Test koneksi Zafa Potato CRM - ' . now()->format('Y-m-d H:i:s');
            
            $result = $this->wablasService->sendMessage($testPhone, $testMessage);
            
            return response()->json([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['success'] ? 'Koneksi berhasil!' : 'Koneksi gagal: ' . $result['error'],
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Test koneksi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics data
     */
    public function getAnalytics(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

            // Overall statistics
            $totalSent = FollowUp::whereBetween('created_at', [$dateFrom, $dateTo])->count();
            $totalDelivered = FollowUp::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'delivered')->count();
            $totalRead = FollowUp::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'read')->count();
            $totalFailed = FollowUp::whereBetween('created_at', [$dateFrom, $dateTo])
                ->where('status', 'failed')->count();

            // Daily statistics
            $dailyStats = FollowUp::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total, 
                           SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                           SUM(CASE WHEN status = "read" THEN 1 ELSE 0 END) as read,
                           SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Target type statistics
            $targetTypeStats = FollowUp::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('target_type, COUNT(*) as total')
                ->groupBy('target_type')
                ->get();

            // Source channel statistics
            $sourceChannelStats = FollowUp::whereBetween('created_at', [$dateFrom, $dateTo])
                ->selectRaw('source_channel, COUNT(*) as total')
                ->whereNotNull('source_channel')
                ->groupBy('source_channel')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'total_sent' => $totalSent,
                        'total_delivered' => $totalDelivered,
                        'total_read' => $totalRead,
                        'total_failed' => $totalFailed,
                        'delivery_rate' => $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 2) : 0,
                        'read_rate' => $totalSent > 0 ? round(($totalRead / $totalSent) * 100, 2) : 0
                    ],
                    'daily_stats' => $dailyStats,
                    'target_type_stats' => $targetTypeStats,
                    'source_channel_stats' => $sourceChannelStats
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat analytics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export follow up history
     */
    public function exportHistory(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $status = $request->get('status');
            $targetType = $request->get('target_type');

            $query = FollowUp::with(['pemesanan', 'customer']);

            if ($dateFrom && $dateTo) {
                $query->whereBetween('created_at', [$dateFrom, $dateTo]);
            }

            if ($status) {
                $query->where('status', $status);
            }

            if ($targetType) {
                $query->where('target_type', $targetType);
            }

            $followUps = $query->orderBy('created_at', 'desc')->get();

            $csvData = [];
            $csvData[] = [
                'ID',
                'Tanggal Kirim',
                'Nama Customer',
                'Nomor HP',
                'Email',
                'Pesan',
                'Status',
                'Target Type',
                'Source Channel',
                'Tanggal Terkirim',
                'Tanggal Diterima',
                'Tanggal Dibaca',
                'Error Message'
            ];

            foreach ($followUps as $followUp) {
                $csvData[] = [
                    $followUp->follow_up_id,
                    $followUp->created_at->format('Y-m-d H:i:s'),
                    $followUp->customer_name,
                    $followUp->phone_number,
                    $followUp->customer_email,
                    $followUp->message,
                    $followUp->status,
                    $followUp->target_type,
                    $followUp->source_channel,
                    $followUp->sent_at ? $followUp->sent_at->format('Y-m-d H:i:s') : '',
                    $followUp->delivered_at ? $followUp->delivered_at->format('Y-m-d H:i:s') : '',
                    $followUp->read_at ? $followUp->read_at->format('Y-m-d H:i:s') : '',
                    $followUp->error_message
                ];
            }

            $filename = 'follow_up_history_' . now()->format('Y_m_d_H_i_s') . '.csv';
            $temp_file = tempnam(sys_get_temp_dir(), $filename);

            $file = fopen($temp_file, 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);

            return response()->download($temp_file, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal export data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete uploaded image
     */
    public function deleteImage($id)
    {
        try {
            $followUp = FollowUp::find($id);
            
            if (!$followUp) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Follow up tidak ditemukan'
                ], 404);
            }

            // Delete image files
            if (!empty($followUp->images)) {
                foreach ($followUp->images as $imagePath) {
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                }
            }

            // Update record
            $followUp->update(['images' => null]);

            return response()->json([
                'status' => 'success',
                'message' => 'Gambar berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus gambar: ' . $e->getMessage()
            ], 500);
        }
    }

}