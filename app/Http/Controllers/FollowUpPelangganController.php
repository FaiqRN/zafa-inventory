<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FollowUpPelangganController extends Controller
{
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
     * Get customer data for filtering and selection
     */
    public function getData(Request $request)
    {
        // Data dummy customer - nanti bisa diganti dengan data dari database
        $customers = [
            [
                'id' => 1,
                'name' => 'Budi Santoso',
                'phone' => '+62 812-3456-7890',
                'email' => 'budi.santoso@email.com',
                'address' => 'Jl. Merdeka No. 123, Malang',
                'lastOrder' => '2025-05-28',
                'totalOrders' => 5,
                'totalSpent' => 'Rp 450,000',
                'customerType' => 'pelangganLama',
                'orderSource' => 'instagram',
                'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face',
                'last' => 'Kentang Goreng Crispy - 2kg',
                'notes' => 'Pelanggan setia, sering pesan untuk acara keluarga'
            ],
            [
                'id' => 2,
                'name' => 'Siti Nurhaliza',
                'phone' => '+62 856-7890-1234',
                'email' => 'siti.nur@email.com',
                'address' => 'Jl. Diponegoro No. 45, Malang',
                'lastOrder' => '2025-05-30',
                'totalOrders' => 3,
                'totalSpent' => 'Rp 275,000',
                'customerType' => 'pelangganBaru',
                'orderSource' => 'langsung',
                'avatar' => 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=100&h=100&fit=crop&crop=face',
                'lastProduct' => 'Paket Kentang Premium - 1.5kg',
                'notes' => 'Suka produk premium, target untuk upselling'
            ],
            [
                'id' => 3,
                'name' => 'Ahmad Rizki',
                'phone' => '+62 821-5678-9012',
                'email' => 'ahmad.rizki@email.com',
                'address' => 'Jl. Veteran No. 78, Malang',
                'lastOrder' => '2025-05-25',
                'totalOrders' => 8,
                'totalSpent' => 'Rp 720,000',
                'customerType' => 'pelangganLama',
                'orderSource' => 'whatsapp',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face',
                'lastProduct' => 'Kentang Bumbu Balado - 3kg',
                'notes' => 'Pelanggan VIP, butuh perhatian khusus'
            ],
            [
                'id' => 4,
                'name' => 'Dewi Sartika',
                'phone' => '+62 813-2468-1357',
                'email' => 'dewi.sartika@email.com',
                'address' => 'Jl. Brawijaya No. 90, Malang',
                'lastOrder' => '2025-06-01',
                'totalOrders' => 2,
                'totalSpent' => 'Rp 180,000',
                'customerType' => 'pelangganBaru',
                'orderSource' => 'shopee',
                'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=100&h=100&fit=crop&crop=face',
                'lastProduct' => 'Kentang Original - 1kg',
                'notes' => 'Baru mencoba produk, potensi repeat order'
            ]
        ];

        // Apply filters if provided
        $search = $request->get('search');
        $customerType = $request->get('customerType');
        $orderSource = $request->get('orderSource');

        if ($search) {
            $customers = array_filter($customers, function($customer) use ($search) {
                return stripos($customer['name'], $search) !== false ||
                       stripos($customer['phone'], $search) !== false ||
                       stripos($customer['email'], $search) !== false;
            });
        }

        if ($customerType) {
            $customers = array_filter($customers, function($customer) use ($customerType) {
                return $customer['customerType'] === $customerType;
            });
        }

        if ($orderSource) {
            $customers = array_filter($customers, function($customer) use ($orderSource) {
                return $customer['orderSource'] === $orderSource;
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => array_values($customers)
        ]);
    }

    /**
     * Send follow up message
     */
    public function sendFollowUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer',
            'message' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $customerId = $request->customer_id;
        $message = $request->message;
        $imagePath = null;

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('follow-up-images', $imageName, 'public');
        }

        // Simulate saving to database
        $followUpData = [
            'id' => rand(1000, 9999), // Generate random ID for demo
            'customer_id' => $customerId,
            'message' => $message ?: 'Mengirim gambar tanpa pesan',
            'image_path' => $imagePath,
            'sent_at' => now(),
            'status' => 'sent'
        ];

        // In real implementation, save to database here
        // FollowUp::create($followUpData);

        return response()->json([
            'status' => 'success',
            'message' => 'Follow up berhasil dikirim!',
            'data' => $followUpData
        ]);
    }

    /**
     * Get follow up history
     */
    public function getHistory(Request $request)
    {
        $customerId = $request->get('customer_id');

        // Data dummy history - nanti bisa diganti dengan data dari database
        $history = [
            [
                'id' => 1,
                'tanggal' => '2025-05-29',
                'pesan' => 'Halo Pak Budi, terima kasih sudah memesan kentang goreng crispy kemarin. Bagaimana rasanya?',
                'gambar' => null,
                'data' => 'Pelanggan Lama, Instagram',
                'customerName' => 'Budi Santoso',
                'customerId' => 1,
                'status' => 'diterima'
            ],
            [
                'id' => 2,
                'tanggal' => '2025-05-31',
                'pesan' => 'Bu Siti, produk premium baru sudah tersedia. Mau coba?',
                'gambar' => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=100&h=100&fit=crop',
                'data' => 'Pelanggan Baru, Langsung',
                'customerName' => 'Siti Nurhaliza',
                'customerId' => 2,
                'status' => 'dibaca'
            ],
            [
                'id' => 3,
                'tanggal' => '2025-05-26',
                'pesan' => 'Pak Ahmad, stok kentang balado terbatas. Mau pesan lagi?',
                'gambar' => null,
                'data' => 'Pelanggan Lama, WhatsApp',
                'customerName' => 'Ahmad Rizki',
                'customerId' => 3,
                'status' => 'terkirim'
            ]
        ];

        // Filter by customer if specified
        if ($customerId) {
            $history = array_filter($history, function($item) use ($customerId) {
                return $item['customerId'] == $customerId;
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => array_values($history)
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
            $imageName = time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('follow-up-images', $imageName, 'public');

            return response()->json([
                'status' => 'success',
                'message' => 'Gambar berhasil diupload',
                'data' => [
                    'path' => $imagePath,
                    'url' => Storage::url($imagePath),
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
}