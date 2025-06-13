<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use App\Models\FollowUp;
use App\Models\Pemesanan;
use App\Models\Customer;
use Carbon\Carbon;

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
     * Get filtered customers data
     */
    public function getFilteredCustomers(Request $request)
    {
        try {
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
                try {
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
                } catch (\Exception $e) {
                    Log::error("Error processing filter {$filter}: " . $e->getMessage());
                    continue;
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

        } catch (\Exception $e) {
            Log::error('getFilteredCustomers error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memuat data customer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send follow up message with FIXED WhatsApp broadcast including images
     */
    public function sendFollowUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customers' => 'required|string',
            'message' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'target_type' => 'required|in:pelangganLama,pelangganBaru,pelangganTidakKembali,keseluruhan'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Decode customers JSON
            $customers = json_decode($request->customers, true);
            if (!$customers || !is_array($customers)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data customer tidak valid'
                ], 422);
            }

            $message = $request->message;
            $targetType = $request->target_type;
            $imagePaths = [];
            $imageUrls = [];

            // FIXED: Handle image uploads with proper storage and URL generation
            if ($request->hasFile('images')) {
                Log::info('Processing ' . count($request->file('images')) . ' uploaded images');
                
                foreach ($request->file('images') as $index => $image) {
                    try {
                        // Validate image
                        if (!$image->isValid()) {
                            Log::error("Invalid image at index {$index}");
                            continue;
                        }

                        // Generate unique filename
                        $imageName = time() . '_' . uniqid() . '_' . $index . '.' . $image->getClientOriginalExtension();
                        
                        // Store in public disk
                        $imagePath = $image->storeAs('follow-up-images', $imageName, 'public');
                        
                        if (!$imagePath) {
                            Log::error("Failed to store image {$imageName}");
                            continue;
                        }

                        $imagePaths[] = $imagePath;
                        
                        // CRITICAL FIX: Generate full accessible URL for WhatsApp API
                        $fullImageUrl = url('storage/' . $imagePath);
                        $imageUrls[] = $fullImageUrl;
                        
                        Log::info("Image uploaded successfully: {$imagePath}, URL: {$fullImageUrl}");
                        
                        // Verify the image is accessible
                        if (!$this->verifyImageUrl($fullImageUrl)) {
                            Log::warning("Image URL not accessible: {$fullImageUrl}");
                        }
                        
                    } catch (\Exception $e) {
                        Log::error("Failed to upload image at index {$index}: " . $e->getMessage());
                        continue;
                    }
                }
                
                Log::info("Total images processed: " . count($imageUrls) . " out of " . count($request->file('images')));
            }

            // Check if we have something to send
            if (empty($message) && empty($imageUrls)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pesan atau gambar harus diisi minimal salah satu'
                ], 422);
            }

            // Check WhatsApp device status first
            $deviceStatus = $this->checkWablasDeviceStatus();
            if (!$deviceStatus['isConnected']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'WhatsApp device tidak terhubung. Status: ' . $deviceStatus['message']
                ], 500);
            }

            Log::info("Starting WhatsApp broadcast to " . count($customers) . " customers with " . count($imageUrls) . " images");

            $successCount = 0;
            $failedCount = 0;
            $results = [];

            // Process customers in smaller batches for image sending
            $batchSize = count($imageUrls) > 0 ? 2 : 5; // Smaller batches when sending images
            $customerBatches = array_chunk($customers, $batchSize);
            
            foreach ($customerBatches as $batchIndex => $batch) {
                Log::info("Processing batch " . ($batchIndex + 1) . " of " . count($customerBatches));
                
                foreach ($batch as $customer) {
                    try {
                        // Validate customer data
                        if (empty($customer['phone']) || empty($customer['name'])) {
                            $results[] = [
                                'customer' => $customer['name'] ?? 'Unknown',
                                'phone' => $customer['phone'] ?? 'Unknown',
                                'status' => 'failed',
                                'error' => 'Data customer tidak lengkap'
                            ];
                            $failedCount++;
                            continue;
                        }

                        // Format phone number
                        $phone = $this->formatPhoneNumber($customer['phone']);
                        if (!$this->validatePhoneNumber($phone)) {
                            $results[] = [
                                'customer' => $customer['name'],
                                'phone' => $customer['phone'],
                                'status' => 'failed',
                                'error' => 'Format nomor telepon tidak valid: ' . $phone
                            ];
                            $failedCount++;
                            continue;
                        }

                        // Create follow up record in database
                        $followUpData = [
                            'customer_name' => $customer['name'],
                            'phone_number' => $phone,
                            'customer_email' => $customer['email'] ?? null,
                            'target_type' => $targetType,
                            'message' => $message,
                            'images' => !empty($imagePaths) ? json_encode($imagePaths) : null,
                            'source_channel' => $customer['orderSource'] ?? null,
                            'status' => 'pending',
                            'created_at' => now(),
                            'updated_at' => now()
                        ];

                        $followUpId = DB::table('follow_up')->insertGetId($followUpData);
                        Log::info("Created follow_up record {$followUpId} for {$customer['name']}");

                        // FIXED: Send via WhatsApp with proper image handling
                        $whatsappResult = $this->sendWhatsAppWithImages($phone, $message, $imageUrls, $customer['name']);
                        
                        if ($whatsappResult['success']) {
                            // Update status to sent
                            DB::table('follow_up')
                                ->where('follow_up_id', $followUpId)
                                ->update([
                                    'status' => 'sent',
                                    'sent_at' => now(),
                                    'wablas_message_id' => $whatsappResult['message_id'] ?? null,
                                    'wablas_response' => json_encode($whatsappResult['response']),
                                    'updated_at' => now()
                                ]);
                            
                            $results[] = [
                                'customer' => $customer['name'],
                                'phone' => $phone,
                                'status' => 'success',
                                'message_id' => $whatsappResult['message_id'] ?? null,
                                'follow_up_id' => $followUpId
                            ];
                            $successCount++;
                            
                            Log::info("Successfully sent WhatsApp with images to {$customer['name']} ({$phone})");
                        } else {
                            // Update status to failed
                            DB::table('follow_up')
                                ->where('follow_up_id', $followUpId)
                                ->update([
                                    'status' => 'failed',
                                    'error_message' => $whatsappResult['error'] ?? 'Unknown error',
                                    'wablas_response' => json_encode($whatsappResult['response']),
                                    'updated_at' => now()
                                ]);
                            
                            $results[] = [
                                'customer' => $customer['name'],
                                'phone' => $phone,
                                'status' => 'failed',
                                'error' => $whatsappResult['error'] ?? 'Gagal mengirim pesan'
                            ];
                            $failedCount++;
                            
                            Log::error("Failed to send WhatsApp to {$customer['name']} ({$phone}): " . $whatsappResult['error']);
                        }

                        // CRITICAL: Longer delay when sending images
                        $delay = count($imageUrls) > 0 ? 6 : 3; // 6 seconds for images, 3 for text only
                        sleep($delay);

                    } catch (\Exception $e) {
                        Log::error('Follow up send error for customer ' . ($customer['name'] ?? 'unknown') . ': ' . $e->getMessage());
                        
                        if (isset($followUpId)) {
                            DB::table('follow_up')
                                ->where('follow_up_id', $followUpId)
                                ->update([
                                    'status' => 'failed',
                                    'error_message' => $e->getMessage(),
                                    'updated_at' => now()
                                ]);
                        }
                        
                        $results[] = [
                            'customer' => $customer['name'] ?? 'Unknown',
                            'phone' => $customer['phone'] ?? 'Unknown',
                            'status' => 'failed',
                            'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
                        ];
                        $failedCount++;
                    }
                }
                
                // Longer delay between batches when sending images
                if ($batchIndex < count($customerBatches) - 1) {
                    $batchDelay = count($imageUrls) > 0 ? 10 : 5;
                    Log::info("Waiting {$batchDelay} seconds before next batch...");
                    sleep($batchDelay);
                }
            }

            Log::info("WhatsApp broadcast completed. Success: {$successCount}, Failed: {$failedCount}");

            return response()->json([
                'status' => 'success',
                'message' => "Follow up selesai. Berhasil: {$successCount}, Gagal: {$failedCount}",
                'summary' => [
                    'total' => count($customers),
                    'success' => $successCount,
                    'failed' => $failedCount,
                    'images_sent' => count($imageUrls)
                ],
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Send follow up error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * FIXED: Send WhatsApp message with images support
     */
    private function sendWhatsAppWithImages($phone, $message, $imageUrls = [], $customerName = 'Customer')
    {
        try {
            $wablasToken = env('WABLAS_TOKEN');
            $wablasUrl = env('WABLAS_API_URL', 'https://texas.wablas.com/api');
            
            if (empty($wablasToken)) {
                return [
                    'success' => false,
                    'error' => 'Wablas token tidak dikonfigurasi',
                    'response' => null
                ];
            }

            $results = [];
            $hasError = false;
            $lastMessageId = null;

            // Headers for API calls
            $headers = [
                'Authorization' => $wablasToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];

            Log::info("Sending WhatsApp to {$phone} with " . count($imageUrls) . " images and message: " . ($message ? 'yes' : 'no'));

            // STRATEGY: Send images first with captions, then text message if needed
            if (!empty($imageUrls)) {
                foreach ($imageUrls as $index => $imageUrl) {
                    // Verify image URL is accessible before sending
                    if (!$this->verifyImageUrl($imageUrl)) {
                        Log::error("Image URL not accessible: {$imageUrl}");
                        $hasError = true;
                        $results[] = [
                            'success' => false,
                            'error' => 'Image URL tidak dapat diakses: ' . $imageUrl
                        ];
                        continue;
                    }

                    // Use message as caption for the first image only
                    $caption = '';
                    if ($index === 0 && !empty($message)) {
                        $caption = $message;
                    } elseif ($index === 0 && empty($message)) {
                        $caption = "Gambar untuk {$customerName}";
                    }
                    
                    $imageResult = $this->sendWablasImageMessage($wablasUrl, $headers, $phone, $imageUrl, $caption);
                    $results[] = $imageResult;
                    
                    if (!$imageResult['success']) {
                        $hasError = true;
                        Log::error("Failed to send image {$index} to {$phone}: " . $imageResult['error']);
                    } else {
                        $lastMessageId = $imageResult['message_id'];
                        Log::info("Successfully sent image {$index} to {$phone}, message_id: {$lastMessageId}");
                    }
                    
                    // Delay between images
                    if ($index < count($imageUrls) - 1) {
                        sleep(3); // 3 seconds between images
                    }
                }
            }

            // Send separate text message only if no images were sent OR if message wasn't used as caption
            if (!empty($message) && (empty($imageUrls) || $hasError)) {
                if (!empty($imageUrls)) {
                    sleep(2); // Small delay after images
                }
                
                $textResult = $this->sendWablasTextMessage($wablasUrl, $headers, $phone, $message);
                $results[] = $textResult;
                
                if (!$textResult['success']) {
                    $hasError = true;
                    Log::error("Failed to send text message to {$phone}: " . $textResult['error']);
                } else {
                    $lastMessageId = $textResult['message_id'];
                    Log::info("Successfully sent text message to {$phone}, message_id: {$lastMessageId}");
                }
            }

            return [
                'success' => !$hasError,
                'message_id' => $lastMessageId,
                'error' => $hasError ? 'Beberapa pesan/gambar gagal dikirim' : null,
                'response' => $results
            ];

        } catch (\Exception $e) {
            Log::error('sendWhatsAppWithImages error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * FIXED: Send image message via Wablas API
     */
    private function sendWablasImageMessage($apiUrl, $headers, $phone, $imageUrl, $caption = '')
    {
        try {
            $payload = [
                'phone' => $phone,
                'image' => $imageUrl,
                'caption' => $caption
            ];
            
            Log::info("Sending image to {$phone}", [
                'url' => $apiUrl . '/send-image',
                'payload' => $payload
            ]);
            
            $response = Http::timeout(90) // Increased timeout for images
                ->withHeaders($headers)
                ->post($apiUrl . '/send-image', $payload);

            Log::info("Wablas image response for {$phone}", [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    $messageId = $this->extractMessageId($responseData);
                    
                    return [
                        'success' => true,
                        'message_id' => $messageId,
                        'response' => $responseData
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $responseData['message'] ?? 'Unknown error from Wablas',
                        'response' => $responseData
                    ];
                }
            } else {
                $errorBody = $response->body();
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status() . ' - ' . $errorBody,
                    'response' => $response->json()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('sendWablasImageMessage error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * FIXED: Send text message via Wablas API
     */
    private function sendWablasTextMessage($apiUrl, $headers, $phone, $message)
    {
        try {
            $payload = [
                'phone' => $phone,
                'message' => $message
            ];
            
            Log::info("Sending text message to {$phone}", [
                'url' => $apiUrl . '/send-message',
                'payload' => $payload
            ]);
            
            $response = Http::timeout(60)
                ->withHeaders($headers)
                ->post($apiUrl . '/send-message', $payload);

            Log::info("Wablas text response for {$phone}", [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    $messageId = $this->extractMessageId($responseData);
                    
                    return [
                        'success' => true,
                        'message_id' => $messageId,
                        'response' => $responseData
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $responseData['message'] ?? 'Unknown error from Wablas',
                        'response' => $responseData
                    ];
                }
            } else {
                $errorBody = $response->body();
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status() . ' - ' . $errorBody,
                    'response' => $response->json()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('sendWablasTextMessage error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * FIXED: Extract message ID from various response formats
     */
    private function extractMessageId($responseData)
    {
        // Try different possible locations for message ID
        if (isset($responseData['data']['messages'][0]['id'])) {
            return $responseData['data']['messages'][0]['id'];
        } elseif (isset($responseData['data']['id'])) {
            return $responseData['data']['id'];
        } elseif (isset($responseData['id'])) {
            return $responseData['id'];
        } elseif (isset($responseData['message_id'])) {
            return $responseData['message_id'];
        } else {
            // Generate fallback ID
            return 'msg_' . time() . '_' . substr(md5(json_encode($responseData)), 0, 8);
        }
    }

    /**
     * NEW: Verify if image URL is accessible
     */
    private function verifyImageUrl($imageUrl)
    {
        try {
            $response = Http::timeout(15)->head($imageUrl);
            
            if (!$response->successful()) {
                Log::error("Image URL HTTP error: " . $response->status() . " for URL: " . $imageUrl);
                return false;
            }
            
            $contentType = $response->header('Content-Type');
            if (!$contentType || strpos($contentType, 'image/') !== 0) {
                Log::error("Invalid content type: " . $contentType . " for URL: " . $imageUrl);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to verify image URL {$imageUrl}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * FIXED: Check device status
     */
    private function checkWablasDeviceStatus()
    {
        try {
            $wablasToken = env('WABLAS_TOKEN');
            $wablasUrl = env('WABLAS_API_URL', 'https://texas.wablas.com/api');
            
            if (empty($wablasToken)) {
                return [
                    'isConnected' => false,
                    'message' => 'Token tidak dikonfigurasi'
                ];
            }

            $headers = [
                'Authorization' => $wablasToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];

            // Test with a simple message to admin
            $testPayload = [
                'phone' => env('APP_ADMIN_PHONE', '6282245454528'),
                'message' => 'Device check - ' . date('H:i:s')
            ];
            
            Log::info("Checking device status via test message");

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post($wablasUrl . '/send-message', $testPayload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    return [
                        'isConnected' => true,
                        'message' => 'connected',
                        'response' => $responseData
                    ];
                } else {
                    return [
                        'isConnected' => false,
                        'message' => $responseData['message'] ?? 'Device validation failed',
                        'response' => $responseData
                    ];
                }
            } else {
                return [
                    'isConnected' => false,
                    'message' => 'HTTP Error: ' . $response->status()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('checkWablasDeviceStatus error: ' . $e->getMessage());
            
            return [
                'isConnected' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * FIXED: Test WhatsApp connection with image support
     */
    public function testWhatsAppConnection()
    {
        try {
            $testPhone = env('APP_ADMIN_PHONE', '6282245454528');
            $testMessage = 'Test koneksi Wablas dengan gambar - ' . now()->format('Y-m-d H:i:s');
            
            Log::info("Testing Wablas connection to: " . $testPhone);
            
            // Test with message only first
            $result = $this->sendWhatsAppWithImages($testPhone, $testMessage, []);
            
            Log::info("Wablas test result: " . json_encode($result));
            
            return response()->json([
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['success'] ? 
                    'Koneksi Wablas berhasil! Pesan test telah dikirim.' : 
                    'Koneksi gagal: ' . $result['error'],
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("Test Wablas connection error: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Test koneksi gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get device status for frontend
     */
    public function getDeviceStatus()
    {
        try {
            $deviceStatus = $this->checkWablasDeviceStatus();
            
            return response()->json([
                'status' => 'success',
                'data' => $deviceStatus
            ]);

        } catch (\Exception $e) {
            Log::error('getDeviceStatus error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'isConnected' => false,
                    'message' => 'Error checking status: ' . $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Format phone number to proper Indonesian format
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Handle empty phone
        if (empty($phone)) {
            return '';
        }
        
        // If starts with 0, replace with 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        // If doesn't start with 62, add 62
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

    /**
     * Validate phone number format
     */
    private function validatePhoneNumber($phone)
    {
        // Must start with 62 and be 12-15 digits total
        $phoneLength = strlen($phone);
        return $phoneLength >= 12 && $phoneLength <= 15 && substr($phone, 0, 2) === '62' && is_numeric($phone);
    }

    /**
     * Get follow up history
     */
    public function getHistory(Request $request)
    {
        try {
            $customerId = $request->get('customer_id');
            $targetType = $request->get('target_type');
            $status = $request->get('status');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            if (!Schema::hasTable('follow_up')) {
                return response()->json([
                    'status' => 'success',
                    'data' => []
                ]);
            }

            $query = DB::table('follow_up');

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

            $history = $query->orderBy('created_at', 'desc')
                           ->limit(50)
                           ->get();

            $formattedHistory = $history->map(function ($item) {
                $images = null;
                if ($item->images) {
                    $imageArray = json_decode($item->images, true);
                    if (is_array($imageArray) && !empty($imageArray)) {
                        $images = asset('storage/' . $imageArray[0]);
                    }
                }

                $statusLabels = [
                    'pending' => '<span class="badge badge-warning">Menunggu</span>',
                    'sent' => '<span class="badge badge-info">Terkirim</span>',
                    'delivered' => '<span class="badge badge-primary">Diterima</span>',
                    'read' => '<span class="badge badge-success">Dibaca</span>',
                    'failed' => '<span class="badge badge-danger">Gagal</span>'
                ];

                return [
                    'id' => 'FU' . str_pad($item->follow_up_id, 4, '0', STR_PAD_LEFT),
                    'tanggal' => Carbon::parse($item->created_at)->format('Y-m-d H:i'),
                    'customerName' => $item->customer_name,
                    'phone' => $item->phone_number,
                    'pesan' => $item->message ?: 'Pesan dengan gambar',
                    'gambar' => $images,
                    'targetType' => $this->getTargetTypeLabel($item->target_type),
                    'status' => $statusLabels[$item->status] ?? '<span class="badge badge-secondary">Unknown</span>',
                    'sourceChannel' => $item->source_channel
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedHistory->toArray()
            ]);

        } catch (\Exception $e) {
            Log::error('getHistory error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }
    }

    /**
     * Upload image for follow up
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120' // 5MB
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
     * Get Pelanggan Lama (>3 transaksi)
     */
    private function getPelangganLama()
    {
        try {
            if (!Schema::hasTable('pemesanan') || !DB::table('pemesanan')->exists()) {
                return collect([]);
            }

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
                ->whereNotNull('nama_pemesan')
                ->whereNotNull('no_telp_pemesan')
                ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan', 'alamat_pemesan', 'pemesanan_dari')
                ->havingRaw('COUNT(*) >= 3')
                ->get();

            return $pelangganLama->map(function ($customer) {
                $lastProduct = 'Unknown Product';
                try {
                    if ($customer->lastOrderId && Schema::hasTable('barang')) {
                        $lastOrder = DB::table('pemesanan')
                            ->leftJoin('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                            ->where('pemesanan.pemesanan_id', $customer->lastOrderId)
                            ->select('barang.nama_barang')
                            ->first();
                        
                        if ($lastOrder && $lastOrder->nama_barang) {
                            $lastProduct = $lastOrder->nama_barang;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Error getting last product for order {$customer->lastOrderId}: " . $e->getMessage());
                }

                return [
                    'id' => 'lama_' . md5($customer->phone ?? 'unknown'),
                    'name' => $customer->name ?? 'Unknown',
                    'phone' => $customer->phone ?? '',
                    'email' => $customer->email ?? '',
                    'address' => $customer->address ?? '',
                    'lastOrder' => $customer->lastOrder ? Carbon::parse($customer->lastOrder)->format('Y-m-d') : '-',
                    'totalOrders' => $customer->totalOrders ?? 0,
                    'totalSpent' => 'Rp ' . number_format($customer->totalSpent ?? 0, 0, ',', '.'),
                    'customerType' => 'pelangganLama',
                    'orderSource' => $customer->orderSource ?? 'unknown',
                    'lastProduct' => $lastProduct,
                    'notes' => 'Pelanggan setia dengan ' . ($customer->totalOrders ?? 0) . ' transaksi',
                    'initial' => $this->getCustomerInitial($customer->name ?? 'UN')
                ];
            });

        } catch (\Exception $e) {
            Log::error('getPelangganLama error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get Pelanggan Baru (1 bulan terakhir, hanya 1 kali transaksi)
     */
    private function getPelangganBaru()
    {
        try {
            if (!Schema::hasTable('pemesanan') || !DB::table('pemesanan')->exists()) {
                return collect([]);
            }

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
                ->whereNotNull('nama_pemesan')
                ->whereNotNull('no_telp_pemesan')
                ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan', 'alamat_pemesan', 'pemesanan_dari', 'tanggal_pemesanan', 'total', 'pemesanan_id')
                ->havingRaw('COUNT(*) = 1')
                ->get();

            return $pelangganBaru->map(function ($customer) {
                $lastProduct = 'Unknown Product';
                try {
                    if ($customer->lastOrderId && Schema::hasTable('barang')) {
                        $lastOrder = DB::table('pemesanan')
                            ->leftJoin('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                            ->where('pemesanan.pemesanan_id', $customer->lastOrderId)
                            ->select('barang.nama_barang')
                            ->first();
                        
                        if ($lastOrder && $lastOrder->nama_barang) {
                            $lastProduct = $lastOrder->nama_barang;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Error getting last product for order {$customer->lastOrderId}: " . $e->getMessage());
                }

                return [
                    'id' => 'baru_' . md5($customer->phone ?? 'unknown'),
                    'name' => $customer->name ?? 'Unknown',
                    'phone' => $customer->phone ?? '',
                    'email' => $customer->email ?? '',
                    'address' => $customer->address ?? '',
                    'lastOrder' => $customer->lastOrder ? Carbon::parse($customer->lastOrder)->format('Y-m-d') : '-',
                    'totalOrders' => 1,
                    'totalSpent' => 'Rp ' . number_format($customer->totalSpent ?? 0, 0, ',', '.'),
                    'customerType' => 'pelangganBaru',
                    'orderSource' => $customer->orderSource ?? 'unknown',
                    'lastProduct' => $lastProduct,
                    'notes' => 'Pelanggan baru, bergabung dalam 1 bulan terakhir',
                    'initial' => $this->getCustomerInitial($customer->name ?? 'UN')
                ];
            });

        } catch (\Exception $e) {
            Log::error('getPelangganBaru error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get Pelanggan Tidak Kembali (>2 bulan tidak transaksi)
     */
    private function getPelangganTidakKembali()
    {
        try {
            if (!Schema::hasTable('pemesanan') || !DB::table('pemesanan')->exists()) {
                return collect([]);
            }

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
                ->whereNotNull('nama_pemesan')
                ->whereNotNull('no_telp_pemesan')
                ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan', 'alamat_pemesan', 'pemesanan_dari')
                ->get();

            return $pelangganTidakKembali->map(function ($customer) {
                $lastProduct = 'Unknown Product';
                try {
                    if ($customer->lastOrderId && Schema::hasTable('barang')) {
                        $lastOrder = DB::table('pemesanan')
                            ->leftJoin('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                            ->where('pemesanan.pemesanan_id', $customer->lastOrderId)
                            ->select('barang.nama_barang')
                            ->first();
                        
                        if ($lastOrder && $lastOrder->nama_barang) {
                            $lastProduct = $lastOrder->nama_barang;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Error getting last product for order {$customer->lastOrderId}: " . $e->getMessage());
                }

                return [
                    'id' => 'tidak_kembali_' . md5($customer->phone ?? 'unknown'),
                    'name' => $customer->name ?? 'Unknown',
                    'phone' => $customer->phone ?? '',
                    'email' => $customer->email ?? '',
                    'address' => $customer->address ?? '',
                    'lastOrder' => $customer->lastOrder ? Carbon::parse($customer->lastOrder)->format('Y-m-d') : '-',
                    'totalOrders' => $customer->totalOrders ?? 0,
                    'totalSpent' => 'Rp ' . number_format($customer->totalSpent ?? 0, 0, ',', '.'),
                    'customerType' => 'pelangganTidakKembali',
                    'orderSource' => $customer->orderSource ?? 'unknown',
                    'lastProduct' => $lastProduct,
                    'notes' => 'Tidak bertransaksi >2 bulan, perlu follow up',
                    'initial' => $this->getCustomerInitial($customer->name ?? 'UN')
                ];
            });

        } catch (\Exception $e) {
            Log::error('getPelangganTidakKembali error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get Keseluruhan Pelanggan
     */
    private function getKeseluruhanPelanggan()
    {
        try {
            $allCustomers = collect();

            // From pemesanan table
            if (Schema::hasTable('pemesanan') && DB::table('pemesanan')->exists()) {
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
                    ->whereNotNull('nama_pemesan')
                    ->whereNotNull('no_telp_pemesan')
                    ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan', 'alamat_pemesan', 'pemesanan_dari')
                    ->get();

                $allCustomers = $allCustomers->merge($fromPemesanan);
            }

            // From data_customer table if exists
            if (Schema::hasTable('data_customer') && DB::table('data_customer')->exists()) {
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

                $allCustomers = $allCustomers->merge($fromCustomer);
            }

            return $allCustomers->map(function ($customer) {
                $lastProduct = 'No Purchase';
                if ($customer->lastOrderId && Schema::hasTable('barang')) {
                    try {
                        $lastOrder = DB::table('pemesanan')
                            ->leftJoin('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                            ->where('pemesanan.pemesanan_id', $customer->lastOrderId)
                            ->select('barang.nama_barang')
                            ->first();
                        
                        if ($lastOrder && $lastOrder->nama_barang) {
                            $lastProduct = $lastOrder->nama_barang;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error getting last product for order {$customer->lastOrderId}: " . $e->getMessage());
                    }
                }

                return [
                    'id' => 'all_' . md5(($customer->phone ?? '') . ($customer->source_table ?? '')),
                    'name' => $customer->name ?? 'Unknown',
                    'phone' => $customer->phone ?? '',
                    'email' => $customer->email ?? '',
                    'address' => $customer->address ?? '',
                    'lastOrder' => $customer->lastOrder ? Carbon::parse($customer->lastOrder)->format('Y-m-d') : '-',
                    'totalOrders' => $customer->totalOrders ?? 0,
                    'totalSpent' => 'Rp ' . number_format($customer->totalSpent ?? 0, 0, ',', '.'),
                    'customerType' => 'keseluruhan',
                    'orderSource' => $customer->orderSource ?? 'unknown',
                    'lastProduct' => $lastProduct,
                    'notes' => 'Data ' . (($customer->source_table ?? '') === 'pemesanan' ? 'dari transaksi' : 'customer manual'),
                    'initial' => $this->getCustomerInitial($customer->name ?? 'UN')
                ];
            });

        } catch (\Exception $e) {
            Log::error('getKeseluruhanPelanggan error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get customers by order source
     */
    private function getPelangganBySource($source)
    {
        try {
            if (!Schema::hasTable('pemesanan') || !DB::table('pemesanan')->exists()) {
                return collect([]);
            }

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
                ->whereNotNull('nama_pemesan')
                ->whereNotNull('no_telp_pemesan')
                ->groupBy('nama_pemesan', 'no_telp_pemesan', 'email_pemesan', 'alamat_pemesan', 'pemesanan_dari')
                ->get();

            return $customers->map(function ($customer) use ($source) {
                $lastProduct = 'Unknown Product';
                try {
                    if ($customer->lastOrderId && Schema::hasTable('barang')) {
                        $lastOrder = DB::table('pemesanan')
                            ->leftJoin('barang', 'pemesanan.barang_id', '=', 'barang.barang_id')
                            ->where('pemesanan.pemesanan_id', $customer->lastOrderId)
                            ->select('barang.nama_barang')
                            ->first();
                        
                        if ($lastOrder && $lastOrder->nama_barang) {
                            $lastProduct = $lastOrder->nama_barang;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Error getting last product for order {$customer->lastOrderId}: " . $e->getMessage());
                }

                return [
                    'id' => $source . '_' . md5($customer->phone ?? 'unknown'),
                    'name' => $customer->name ?? 'Unknown',
                    'phone' => $customer->phone ?? '',
                    'email' => $customer->email ?? '',
                    'address' => $customer->address ?? '',
                    'lastOrder' => $customer->lastOrder ? Carbon::parse($customer->lastOrder)->format('Y-m-d') : '-',
                    'totalOrders' => $customer->totalOrders ?? 0,
                    'totalSpent' => 'Rp ' . number_format($customer->totalSpent ?? 0, 0, ',', '.'),
                    'customerType' => $this->determineCustomerType($customer->totalOrders ?? 0, $customer->lastOrder),
                    'orderSource' => $customer->orderSource ?? $source,
                    'lastProduct' => $lastProduct,
                    'notes' => 'Customer dari ' . $source,
                    'initial' => $this->getCustomerInitial($customer->name ?? 'UN')
                ];
            });

        } catch (\Exception $e) {
            Log::error("getPelangganBySource({$source}) error: " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Helper Methods
     */
    private function getCustomerInitial($name)
    {
        if (empty($name)) {
            return 'UN';
        }
        
        $words = explode(' ', trim($name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($name, 0, 2));
    }

    private function determineCustomerType($totalOrders, $lastOrder)
    {
        if (!$lastOrder) {
            return 'keseluruhan';
        }

        try {
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
        } catch (\Exception $e) {
            return 'keseluruhan';
        }
    }

    private function getTargetTypeLabel($targetType)
    {
        $labels = [
            'pelangganLama' => 'Pelanggan Lama',
            'pelangganBaru' => 'Pelanggan Baru',
            'pelangganTidakKembali' => 'Pelanggan Tidak Kembali',
            'keseluruhan' => 'Keseluruhan'
        ];
        
        return $labels[$targetType] ?? 'Unknown';
    }

    /**
     * Debug function to check database tables and data
     */
    public function debugDatabase()
    {
        try {
            $debug = [];
            
            $debug['tables_exist'] = [
                'pemesanan' => Schema::hasTable('pemesanan'),
                'data_customer' => Schema::hasTable('data_customer'),
                'barang' => Schema::hasTable('barang'),
                'follow_up' => Schema::hasTable('follow_up')
            ];
            
            if (Schema::hasTable('pemesanan')) {
                $debug['pemesanan_count'] = DB::table('pemesanan')->count();
                $debug['pemesanan_sample'] = DB::table('pemesanan')->limit(3)->get();
            }
            
            if (Schema::hasTable('follow_up')) {
                $debug['follow_up_count'] = DB::table('follow_up')->count();
                $debug['follow_up_sample'] = DB::table('follow_up')->limit(3)->get();
            }
            
            // Test Wablas configuration
            $debug['wablas_config'] = [
                'token_exists' => !empty(env('WABLAS_TOKEN')),
                'api_url' => env('WABLAS_API_URL', 'https://texas.wablas.com/api'),
                'admin_phone' => env('APP_ADMIN_PHONE', 'not_set')
            ];
            
            // Test device status
            $debug['device_status'] = $this->checkWablasDeviceStatus();
            
            // Test customer queries
            try {
                $debug['pelangganLama_count'] = $this->getPelangganLama()->count();
            } catch (\Exception $e) {
                $debug['pelangganLama_error'] = $e->getMessage();
            }
            
            try {
                $debug['pelangganBaru_count'] = $this->getPelangganBaru()->count();
            } catch (\Exception $e) {
                $debug['pelangganBaru_error'] = $e->getMessage();
            }
            
            try {
                $debug['pelangganTidakKembali_count'] = $this->getPelangganTidakKembali()->count();
            } catch (\Exception $e) {
                $debug['pelangganTidakKembali_error'] = $e->getMessage();
            }
            
            try {
                $debug['keseluruhan_count'] = $this->getKeseluruhanPelanggan()->count();
            } catch (\Exception $e) {
                $debug['keseluruhan_error'] = $e->getMessage();
            }

            // Test storage
            $debug['storage_info'] = [
                'public_path_exists' => file_exists(public_path('storage')),
                'storage_app_public_exists' => file_exists(storage_path('app/public')),
                'follow_up_images_dir_exists' => file_exists(storage_path('app/public/follow-up-images')),
                'storage_writable' => is_writable(storage_path('app/public'))
            ];
            
            return response()->json([
                'status' => 'success',
                'debug' => $debug
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}