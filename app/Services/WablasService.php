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
        $this->apiUrl = config('services.wablas.api_url', 'https://console.wablas.com/api');
        $this->token = config('services.wablas.token');
    }

    /**
     * Send text message
     */
    public function sendMessage($phone, $message)
    {
        try {
            $response = Http::post($this->apiUrl . '/send-message', [
                'phone' => $this->formatPhoneNumber($phone),
                'message' => $message,
                'token' => $this->token
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData['status'] === true) {
                    return [
                        'success' => true,
                        'message_id' => $responseData['data']['id'] ?? null,
                        'response' => $responseData
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $responseData['message'] ?? 'Unknown error',
                        'response' => $responseData
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status(),
                    'response' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Wablas send message error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Send image with caption
     */
    public function sendImage($phone, $imageUrl, $caption = '')
    {
        try {
            $response = Http::post($this->apiUrl . '/send-image', [
                'phone' => $this->formatPhoneNumber($phone),
                'image' => $imageUrl,
                'caption' => $caption,
                'token' => $this->token
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData['status'] === true) {
                    return [
                        'success' => true,
                        'message_id' => $responseData['data']['id'] ?? null,
                        'response' => $responseData
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $responseData['message'] ?? 'Unknown error',
                        'response' => $responseData
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status(),
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
     * Send multiple images
     */
    public function sendMultipleImages($phone, $images, $caption = '')
    {
        $results = [];
        
        foreach ($images as $index => $imageUrl) {
            $messageCaption = $index === 0 ? $caption : ''; // Only add caption to first image
            $result = $this->sendImage($phone, $imageUrl, $messageCaption);
            $results[] = $result;
            
            // Add small delay between messages
            if ($index < count($images) - 1) {
                sleep(1);
            }
        }
        
        return $results;
    }

    /**
     * Send message with images
     */
    public function sendMessageWithImages($phone, $message, $images = [])
    {
        $results = [];
        
        // Send text message first if provided
        if (!empty($message)) {
            $results['message'] = $this->sendMessage($phone, $message);
        }
        
        // Send images if provided
        if (!empty($images)) {
            $results['images'] = $this->sendMultipleImages($phone, $images);
        }
        
        return $results;
    }

    /**
     * Check message status
     */
    public function checkMessageStatus($messageId)
    {
        try {
            $response = Http::get($this->apiUrl . '/message-status', [
                'id' => $messageId,
                'token' => $this->token
            ]);

            if ($response->successful()) {
                return $response->json();
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
     * Get device status
     */
    public function getDeviceStatus()
    {
        try {
            $response = Http::get($this->apiUrl . '/device-status', [
                'token' => $this->token
            ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Wablas device status error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
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
    public function validatePhoneNumber($phone)
    {
        $formatted = $this->formatPhoneNumber($phone);
        
        // Indonesian phone number should be 10-13 digits after country code
        $phoneLength = strlen($formatted);
        
        return $phoneLength >= 12 && $phoneLength <= 15 && substr($formatted, 0, 2) === '62';
    }
}