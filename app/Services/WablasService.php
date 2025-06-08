<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WablasService
{
    private $apiUrl;
    private $token;
    private $secretKey;

    public function __construct()
    {
        $this->apiUrl = config('services.wablas.api_url') ?? env('WABLAS_API_URL', 'https://texas.wablas.com/api');
        $this->token = config('services.wablas.token') ?? env('WABLAS_TOKEN');
        $this->secretKey = config('services.wablas.secret_key') ?? env('WABLAS_SECRET_KEY');
        
        // DEBUG: Log configuration values (remove in production)
        Log::info('WablasService Config Debug', [
            'api_url' => $this->apiUrl,
            'token_exists' => !empty($this->token),
            'token_preview' => $this->token ? substr($this->token, 0, 20) . '...' : 'NOT SET',
            'secret_key_exists' => !empty($this->secretKey),
            'secret_key_preview' => $this->secretKey ? substr($this->secretKey, 0, 4) . '...' : 'NOT SET'
        ]);
    }

    /**
     * Get proper authorization header for Texas Wablas v2
     */
    private function getAuthToken()
    {
        if (empty($this->token) || empty($this->secretKey)) {
            Log::error('WablasService: Missing token or secret key', [
                'token_empty' => empty($this->token),
                'secret_key_empty' => empty($this->secretKey)
            ]);
            return null;
        }
        
        $authToken = trim($this->token) . '.' . trim($this->secretKey);
        
        // DEBUG: Log auth token format (remove in production)
        Log::info('WablasService Auth Token Debug', [
            'auth_token_preview' => substr($authToken, 0, 30) . '...',
            'auth_token_length' => strlen($authToken),
            'token_length' => strlen($this->token),
            'secret_key_length' => strlen($this->secretKey)
        ]);
        
        return $authToken;
    }


    /**
     * Send text message using Texas Wablas v2 API
     */
    public function sendMessage($phone, $message)
    {
        try {
            $authToken = $this->getAuthToken();
            
            if (!$authToken) {
                return [
                    'success' => false,
                    'error' => 'Token atau secret key tidak dikonfigurasi',
                    'response' => null
                ];
            }

            $formattedPhone = $this->formatPhoneNumber($phone);
            if (!$this->validatePhoneNumber($formattedPhone)) {
                return [
                    'success' => false,
                    'error' => 'Format nomor telepon tidak valid: ' . $formattedPhone,
                    'response' => null
                ];
            }

            // Use Texas Wablas v2 API format
            $payload = [
                "data" => [
                    [
                        'phone' => $formattedPhone,
                        'message' => $message
                    ]
                ]
            ];

            Log::info('WablasService: Sending message', [
                'phone' => $formattedPhone,
                'message_length' => strlen($message),
                'payload' => $payload
            ]);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => $authToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($this->apiUrl . '/v2/send-message', $payload);

            Log::info('WablasService: Send message response', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    $messageId = null;
                    
                    if (isset($responseData['data']['messages'][0]['id'])) {
                        $messageId = $responseData['data']['messages'][0]['id'];
                    } elseif (isset($responseData['data']['device_id'])) {
                        $messageId = $responseData['data']['device_id'] . '_' . time();
                    }
                    
                    return [
                        'success' => true,
                        'message_id' => $messageId,
                        'response' => $responseData
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $responseData['message'] ?? 'Unknown error from Texas Wablas',
                        'response' => $responseData
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status() . ' - ' . $response->body(),
                    'response' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::error('WablasService: Send message error', [
                'error' => $e->getMessage(),
                'phone' => $phone
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Send image with caption using Texas Wablas v2 API
     */
    public function sendImage($phone, $imageUrl, $caption = '')
    {
        try {
            if (empty($this->token) || empty($this->secretKey)) {
                return [
                    'success' => false,
                    'error' => 'Token atau secret key tidak dikonfigurasi',
                    'response' => null
                ];
            }

            $formattedPhone = $this->formatPhoneNumber($phone);
            if (!$this->validatePhoneNumber($formattedPhone)) {
                return [
                    'success' => false,
                    'error' => 'Format nomor telepon tidak valid: ' . $formattedPhone,
                    'response' => null
                ];
            }

            // FIXED: Use Texas Wablas v2 API format for images
            $payload = [
                "data" => [
                    [
                        'phone' => $formattedPhone,
                        'image' => $imageUrl,
                        'caption' => $caption
                    ]
                ]
            ];

            Log::info('Sending image via Texas Wablas v2', [
                'phone' => $formattedPhone,
                'image_url' => $imageUrl,
                'caption_length' => strlen($caption)
            ]);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => $this->getAuthToken(),
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrl . '/v2/send-image', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    $messageId = null;
                    
                    if (isset($responseData['data']['messages'][0]['id'])) {
                        $messageId = $responseData['data']['messages'][0]['id'];
                    } elseif (isset($responseData['data']['device_id'])) {
                        $messageId = $responseData['data']['device_id'] . '_' . time();
                    }
                    
                    return [
                        'success' => true,
                        'message_id' => $messageId,
                        'response' => $responseData
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $responseData['message'] ?? 'Unknown error from Texas Wablas',
                        'response' => $responseData
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status() . ' - ' . $response->body(),
                    'response' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Wablas send image error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Send multiple images with optimized batch processing
     */
    public function sendMultipleImages($phone, $images, $caption = '')
    {
        $results = [];
        
        foreach ($images as $index => $imageUrl) {
            $messageCaption = ($index === 0) ? $caption : ''; // Only add caption to first image
            $result = $this->sendImage($phone, $imageUrl, $messageCaption);
            $results[] = $result;
            
            // Add delay between images to prevent rate limiting
            if ($index < count($images) - 1) {
                sleep(2); // 2 second delay between images
            }
        }
        
        return $results;
    }

    /**
     * Send bulk messages using Texas Wablas v2 batch API
     */
    public function sendBulkMessages($messages)
    {
        try {
            if (empty($this->token) || empty($this->secretKey)) {
                return [
                    'success' => false,
                    'error' => 'Token atau secret key tidak dikonfigurasi',
                    'response' => null
                ];
            }

            // Prepare bulk data
            $bulkData = [];
            foreach ($messages as $msg) {
                $formattedPhone = $this->formatPhoneNumber($msg['phone']);
                if ($this->validatePhoneNumber($formattedPhone)) {
                    $bulkData[] = [
                        'phone' => $formattedPhone,
                        'message' => $msg['message']
                    ];
                }
            }

            if (empty($bulkData)) {
                return [
                    'success' => false,
                    'error' => 'Tidak ada nomor valid untuk dikirim',
                    'response' => null
                ];
            }

            // Split into chunks of 100 (Texas Wablas limit)
            $chunks = array_chunk($bulkData, 100);
            $allResults = [];

            foreach ($chunks as $chunkIndex => $chunk) {
                $payload = ["data" => $chunk];

                Log::info("Sending bulk chunk {$chunkIndex} via Texas Wablas v2", [
                    'chunk_size' => count($chunk),
                    'total_chunks' => count($chunks)
                ]);

                $response = Http::timeout(120) // Longer timeout for bulk
                    ->withHeaders([
                        'Authorization' => $this->getAuthToken(),
                        'Content-Type' => 'application/json'
                    ])
                    ->post($this->apiUrl . '/v2/send-message', $payload);

                if ($response->successful()) {
                    $responseData = $response->json();
                    $allResults[] = $responseData;
                } else {
                    $allResults[] = [
                        'success' => false,
                        'error' => 'HTTP Error: ' . $response->status(),
                        'response' => $response->json()
                    ];
                }

                // Delay between chunks to prevent rate limiting
                if ($chunkIndex < count($chunks) - 1) {
                    sleep(5); // 5 second delay between chunks
                }
            }

            return [
                'success' => true,
                'results' => $allResults,
                'total_chunks' => count($chunks),
                'total_messages' => count($bulkData)
            ];

        } catch (\Exception $e) {
            Log::error('Wablas bulk send error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Send message with images combined
     */
    public function sendMessageWithImages($phone, $message, $images = [])
    {
        $results = [];
        
        // Send text message first if provided
        if (!empty($message)) {
            $results['message'] = $this->sendMessage($phone, $message);
            
            // Add delay before sending images
            if (!empty($images)) {
                sleep(2);
            }
        }
        
        // Send images if provided
        if (!empty($images)) {
            $results['images'] = $this->sendMultipleImages($phone, $images);
        }
        
        return $results;
    }

    /**
     * Check message status (if supported by Texas Wablas)
     */
    public function checkMessageStatus($messageId)
    {
        try {
            if (empty($this->token) || empty($this->secretKey)) {
                return [
                    'success' => false,
                    'error' => 'Token atau secret key tidak dikonfigurasi'
                ];
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $this->getAuthToken(),
                ])
                ->get($this->apiUrl . '/report/message', [
                    'message_id' => $messageId
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'response' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Wablas check status error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get device status using Texas Wablas v2
     */
    public function getDeviceStatus()
    {
        try {
            $authToken = $this->getAuthToken();
            
            if (!$authToken) {
                return [
                    'success' => false,
                    'error' => 'Token atau secret key tidak dikonfigurasi',
                    'debug' => [
                        'token_exists' => !empty($this->token),
                        'secret_key_exists' => !empty($this->secretKey)
                    ]
                ];
            }

            Log::info('WablasService: Checking device status', [
                'url' => $this->apiUrl . '/device/info',
                'auth_preview' => substr($authToken, 0, 30) . '...'
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $authToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->get($this->apiUrl . '/device/info');

            // DEBUG: Log full response details
            Log::info('WablasService Response Debug', [
                'status_code' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Parse Texas Wablas device status format
                $isConnected = false;
                $status = 'unknown';
                
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    if (isset($responseData['data']['status'])) {
                        $status = $responseData['data']['status'];
                        $isConnected = ($status === 'connected');
                    }
                }
                
                return [
                    'success' => true,
                    'isConnected' => $isConnected,
                    'status' => $status,
                    'response' => $responseData
                ];
            } else {
                Log::error('WablasService: HTTP Error', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'auth_token_used' => substr($authToken, 0, 30) . '...'
                ]);
                
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status() . ' - ' . $response->body(),
                    'debug' => [
                        'status_code' => $response->status(),
                        'response_body' => $response->body(),
                        'url' => $this->apiUrl . '/device/info',
                        'auth_preview' => substr($authToken, 0, 30) . '...'
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error('WablasService: Exception occurred', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => [
                    'exception' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ];
        }
    }

    /**
     * Test connection to Texas Wablas
     */
    public function testConnection($testPhone = null)
    {
        try {
            $testPhone = $testPhone ?: config('app.admin_phone', '6282245454528');
            $testMessage = 'Test koneksi Texas Wablas v2 - ' . now()->format('Y-m-d H:i:s');
            
            Log::info("Testing Texas Wablas connection to: {$testPhone}");
            
            // First check device status
            $deviceStatus = $this->getDeviceStatus();
            if (!$deviceStatus['success']) {
                return [
                    'success' => false,
                    'error' => 'Gagal mengecek status device: ' . $deviceStatus['error']
                ];
            }
            
            if (!$deviceStatus['isConnected']) {
                return [
                    'success' => false,
                    'error' => 'Device WhatsApp tidak terhubung. Status: ' . $deviceStatus['status']
                ];
            }
            
            // Send test message
            $result = $this->sendMessage($testPhone, $testMessage);
            
            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 
                    'Test berhasil! Pesan telah dikirim ke ' . $testPhone : 
                    'Test gagal: ' . $result['error'],
                'data' => $result
            ];
            
        } catch (\Exception $e) {
            Log::error('Test connection error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to proper Indonesian format
     */
    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (empty($phone)) {
            return '';
        }
        
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

    /**
     * Validate phone number format for Indonesian numbers
     */
    public function validatePhoneNumber($phone)
    {
        $formatted = $this->formatPhoneNumber($phone);
        $phoneLength = strlen($formatted);
        
        return $phoneLength >= 12 && $phoneLength <= 15 && 
               substr($formatted, 0, 2) === '62' && 
               is_numeric($formatted);
    }

    /**
     * Generate QR Code for device connection
     */
    public function generateQRCode()
    {
        try {
            if (empty($this->token) || empty($this->secretKey)) {
                return [
                    'success' => false,
                    'error' => 'Token atau secret key tidak dikonfigurasi'
                ];
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $this->getAuthToken(),
                ])
                ->get($this->apiUrl . '/device/scan');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'qr_url' => $this->apiUrl . '/device/scan?token=' . $this->getAuthToken(),
                    'response' => $response->body()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Generate QR Code error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}