<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WablasService
{
    private $apiUrl;
    private $token;

    public function __construct()
    {
        $this->apiUrl = config('services.wablas.api_url') ?? env('WABLAS_API_URL', 'https://texas.wablas.com/api');
        $this->token = config('services.wablas.token') ?? env('WABLAS_TOKEN');
        
        // DEBUG: Log configuration values (remove in production)
        Log::info('WablasService Config (FINAL FIX)', [
            'api_url' => $this->apiUrl,
            'token_exists' => !empty($this->token),
            'token_preview' => $this->token ? substr($this->token, 0, 20) . '...' : 'NOT SET',
        ]);
    }

    /**
     * FINAL FIX: Get proper authorization headers
     */
    private function getHeaders()
    {
        if (empty($this->token)) {
            Log::error('WablasService: Missing token');
            return null;
        }
        
        return [
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    /**
     * FINAL FIX: Send text message (sudah terbukti berhasil dari test)
     */
    public function sendMessage($phone, $message)
    {
        try {
            $headers = $this->getHeaders();
            
            if (!$headers) {
                return [
                    'success' => false,
                    'error' => 'Token tidak dikonfigurasi',
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

            // FINAL FIX: Format yang sudah terbukti berhasil dari test
            $payload = [
                'phone' => $formattedPhone,
                'message' => $message
            ];

            Log::info('WablasService: Sending message (FINAL FIX)', [
                'phone' => $formattedPhone,
                'message_length' => strlen($message),
                'url' => $this->apiUrl . '/send-message',
                'payload' => $payload
            ]);

            $response = Http::timeout(60)
                ->withHeaders($headers)
                ->post($this->apiUrl . '/send-message', $payload);

            Log::info('WablasService: Send message response (FINAL FIX)', [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // FINAL FIX: Handle response format berdasarkan test yang berhasil
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    $messageId = null;
                    
                    // Extract message ID dari berbagai format response
                    if (isset($responseData['data']['messages'][0]['id'])) {
                        $messageId = $responseData['data']['messages'][0]['id'];
                    } elseif (isset($responseData['data']['id'])) {
                        $messageId = $responseData['data']['id'];
                    } elseif (isset($responseData['id'])) {
                        $messageId = $responseData['id'];
                    } elseif (isset($responseData['message_id'])) {
                        $messageId = $responseData['message_id'];
                    } else {
                        $messageId = 'sent_' . time();
                    }
                    
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
                Log::error('WablasService: HTTP Error', [
                    'status' => $response->status(),
                    'response' => $errorBody
                ]);
                
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status() . ' - ' . $errorBody,
                    'response' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::error('WablasService: Send message error', [
                'error' => $e->getMessage(),
                'phone' => $phone,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * FINAL FIX: Send image (menggunakan format yang sama dengan send message)
     */
    public function sendImage($phone, $imageUrl, $caption = '')
    {
        try {
            $headers = $this->getHeaders();
            
            if (!$headers) {
                return [
                    'success' => false,
                    'error' => 'Token tidak dikonfigurasi',
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

            // FINAL FIX: Format yang konsisten dengan send message
            $payload = [
                'phone' => $formattedPhone,
                'image' => $imageUrl,
                'caption' => $caption
            ];

            Log::info('WablasService: Sending image (FINAL FIX)', [
                'phone' => $formattedPhone,
                'image_url' => $imageUrl,
                'caption_length' => strlen($caption),
                'url' => $this->apiUrl . '/send-image'
            ]);

            $response = Http::timeout(60)
                ->withHeaders($headers)
                ->post($this->apiUrl . '/send-image', $payload);

            Log::info('WablasService: Send image response (FINAL FIX)', [
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    $messageId = null;
                    
                    if (isset($responseData['data']['messages'][0]['id'])) {
                        $messageId = $responseData['data']['messages'][0]['id'];
                    } elseif (isset($responseData['data']['id'])) {
                        $messageId = $responseData['data']['id'];
                    } elseif (isset($responseData['id'])) {
                        $messageId = $responseData['id'];
                    } else {
                        $messageId = 'image_' . time();
                    }
                    
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
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status() . ' - ' . $response->body(),
                    'response' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::error('WablasService: Send image error', [
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
     * FINAL FIX: Get device status - coba send test message untuk validasi
     */
    public function getDeviceStatus()
    {
        try {
            $headers = $this->getHeaders();
            
            if (!$headers) {
                return [
                    'success' => false,
                    'error' => 'Token tidak dikonfigurasi',
                    'isConnected' => false,
                    'status' => 'no_token',
                    'message' => 'Token tidak dikonfigurasi'
                ];
            }

            // FINAL FIX: Karena endpoint status tidak bekerja, gunakan method alternatif
            // Coba kirim test message untuk validasi device connection
            $testResult = $this->validateDeviceByTestMessage();
            
            return [
                'success' => $testResult['connected'],
                'isConnected' => $testResult['connected'],
                'status' => $testResult['connected'] ? 'connected' : 'disconnected',
                'message' => $testResult['message'],
                'response' => $testResult
            ];

        } catch (\Exception $e) {
            Log::error('WablasService: Exception checking device status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'isConnected' => false,
                'status' => 'exception',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * FINAL FIX: Validate device connection dengan test message minimal
     */
    private function validateDeviceByTestMessage()
    {
        try {
            $headers = $this->getHeaders();
            
            // Test dengan payload minimal untuk validasi
            $testPayload = [
                'phone' => '6282245454528', // Admin phone
                'message' => 'Device validation test - ' . date('H:i:s')
            ];

            Log::info('WablasService: Validating device connection (FINAL FIX)');

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post($this->apiUrl . '/send-message', $testPayload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    return [
                        'connected' => true,
                        'message' => 'Device connected and ready',
                        'test_response' => $responseData
                    ];
                } else {
                    return [
                        'connected' => false,
                        'message' => $responseData['message'] ?? 'Device validation failed',
                        'test_response' => $responseData
                    ];
                }
            } else {
                return [
                    'connected' => false,
                    'message' => 'HTTP Error: ' . $response->status(),
                    'test_response' => $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('validateDeviceByTestMessage error: ' . $e->getMessage());
            
            return [
                'connected' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'test_response' => null
            ];
        }
    }

    /**
     * FINAL FIX: Test connection
     */
    public function testConnection($testPhone = null)
    {
        try {
            $testPhone = $testPhone ?: '6282245454528';
            $testMessage = 'Test koneksi Wablas FINAL FIX - ' . now()->format('Y-m-d H:i:s');
            
            Log::info("Testing Wablas connection (FINAL FIX) to: {$testPhone}");
            
            // Langsung test send message (sudah terbukti berhasil)
            $result = $this->sendMessage($testPhone, $testMessage);
            
            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 
                    'Test berhasil! Pesan telah dikirim ke ' . $testPhone : 
                    'Test gagal: ' . $result['error'],
                'data' => $result
            ];
            
        } catch (\Exception $e) {
            Log::error('Test connection error (FINAL FIX): ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
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
}