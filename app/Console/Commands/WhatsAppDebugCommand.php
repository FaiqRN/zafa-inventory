<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WablasService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class WhatsAppDebugCommand extends Command
{
    protected $signature = 'whatsapp:debug 
                           {--check-config : Check configuration only}
                           {--check-device : Check device status only}
                           {--test-send : Send test message}
                           {--phone= : Phone number for test message}
                           {--test-endpoints : Test all endpoints}';

    protected $description = 'Debug WhatsApp integration with Wablas v2 (FIXED VERSION)';

    public function handle()
    {
        $this->info('🔍 Wablas v2 WhatsApp Debug Tool (FIXED VERSION)');
        $this->info('==================================================');

        // STEP 1: Debug Environment Loading
        $this->debugEnvironment();

        if ($this->option('check-config')) {
            $this->checkConfiguration();
            return 0;
        }

        if ($this->option('check-device')) {
            $this->checkDeviceStatus();
            return 0;
        }

        if ($this->option('test-send')) {
            $this->sendTestMessage();
            return 0;
        }

        if ($this->option('test-endpoints')) {
            $this->testAllEndpoints();
            return 0;
        }

        // Default: run all checks
        $this->checkConfiguration();
        $this->checkDeviceStatus();
        $this->testBasicConnection();
        
        return 0;
    }

    private function debugEnvironment()
    {
        $this->newLine();
        $this->info('🔧 Environment Debug (FIXED):');
        
        // Check if .env is loaded
        $envPath = base_path('.env');
        $this->info("📁 .env file exists: " . (file_exists($envPath) ? '✅ Yes' : '❌ No'));
        
        // Check raw env() values
        $this->info("🔑 Raw env() values:");
        $this->line("   WABLAS_API_URL: " . (env('WABLAS_API_URL') ?: '❌ NOT SET'));
        $this->line("   WABLAS_TOKEN: " . (env('WABLAS_TOKEN') ? '✅ SET (' . strlen(env('WABLAS_TOKEN')) . ' chars)' : '❌ NOT SET'));
        $this->line("   WABLAS_SECRET_KEY: " . (env('WABLAS_SECRET_KEY') ? '✅ SET (' . strlen(env('WABLAS_SECRET_KEY')) . ' chars)' : '❌ NOT SET'));
        $this->line("   WABLAS_DEVICE_ID: " . (env('WABLAS_DEVICE_ID') ?: '❌ NOT SET'));
        
        // Check config() values
        $this->info("⚙️  Config values:");
        $this->line("   services.wablas.api_url: " . (config('services.wablas.api_url') ?: '❌ NOT SET'));
        $this->line("   services.wablas.token: " . (config('services.wablas.token') ? '✅ SET' : '❌ NOT SET'));
        
        // FIXED: Show proper authorization format for Wablas v2
        $token = env('WABLAS_TOKEN');
        if ($token) {
            $this->info("🔐 Authorization Header Format (FIXED):");
            $this->line("   Authorization: " . substr($token, 0, 30) . '...');
            $this->warn("   ⚠️  NOTE: Wablas v2 uses token directly, NOT token + secret key!");
        }
    }

    private function checkConfiguration()
    {
        $this->newLine();
        $this->info('⚙️  Checking Configuration (FIXED for Wablas v2)...');

        $config = [
            'API URL' => env('WABLAS_API_URL', 'https://texas.wablas.com/api'),
            'Token' => env('WABLAS_TOKEN') ? 'SET (' . strlen(env('WABLAS_TOKEN')) . ' characters)' : 'NOT SET',
            'Device ID' => env('WABLAS_DEVICE_ID') ?: 'NOT SET',
            'Admin Phone' => env('APP_ADMIN_PHONE') ?: 'NOT SET',
        ];

        $allConfigured = true;
        foreach ($config as $key => $value) {
            $status = (strpos($value, 'NOT SET') === false) ? '✅' : '❌';
            if ($status === '❌') $allConfigured = false;
            $this->line("   {$status} {$key}: {$value}");
        }

        if ($allConfigured) {
            $this->info('✅ All required configurations are set');
            $this->warn('💡 IMPORTANT: Wablas v2 only requires TOKEN, secret key is not used in API calls!');
        } else {
            $this->error('❌ Some configurations are missing');
            $this->info('💡 Check your .env file and make sure WABLAS_TOKEN is set');
        }
    }

    private function checkDeviceStatus()
    {
        $this->newLine();
        $this->info('📱 Checking Device Status (FIXED for Wablas v2)...');

        try {
            $token = env('WABLAS_TOKEN');
            $apiUrl = env('WABLAS_API_URL', 'https://texas.wablas.com/api');
            
            if (empty($token)) {
                $this->error('❌ Token not configured');
                return;
            }

            // FIXED: Use proper Wablas v2 authorization and endpoint
            $headers = [
                'Authorization' => $token,  // FIXED: Token saja, bukan token + secret
                'Accept' => 'application/json'
            ];

            // Try multiple possible endpoints for device status
            $endpoints = [
                '/device/status',
                '/device',
                '/status',
                '/device/info'
            ];

            $deviceConnected = false;
            $workingEndpoint = null;
            $lastResponse = null;

            foreach ($endpoints as $endpoint) {
                try {
                    $fullUrl = $apiUrl . $endpoint;
                    $this->line("   Testing endpoint: {$fullUrl}");
                    
                    $response = Http::timeout(15)
                        ->withHeaders($headers)
                        ->get($fullUrl);

                    $this->line("   Response code: " . $response->status());
                    
                    if ($response->successful()) {
                        $responseData = $response->json();
                        $lastResponse = $responseData;
                        $workingEndpoint = $endpoint;
                        
                        // Check various possible response formats
                        if (isset($responseData['status']) && $responseData['status'] === true) {
                            $deviceConnected = true;
                            break;
                        } elseif (isset($responseData['device_status']) && in_array(strtolower($responseData['device_status']), ['connected', 'ready', 'authenticated'])) {
                            $deviceConnected = true;
                            break;
                        } elseif (isset($responseData['connected']) && $responseData['connected'] === true) {
                            $deviceConnected = true;
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    $this->line("   Error: " . $e->getMessage());
                    continue;
                }
            }

            if ($workingEndpoint) {
                $this->info("✅ Working endpoint found: {$workingEndpoint}");
                $this->line("   Device status: " . ($deviceConnected ? '🟢 Connected' : '🔴 Disconnected'));
                
                if ($lastResponse) {
                    $this->info('📊 Raw response:');
                    $this->line('   ' . json_encode($lastResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
                
                if (!$deviceConnected) {
                    $this->warn('⚠️  Device not connected. Please scan QR code in Wablas dashboard.');
                }
            } else {
                $this->error('❌ No working device status endpoint found');
                $this->info('💡 Possible solutions:');
                $this->line('   1. Check if your token is valid');
                $this->line('   2. Verify API URL is correct');
                $this->line('   3. Check Wablas dashboard for device status');
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
        }
    }

    private function testBasicConnection()
    {
        $this->newLine();
        $this->info('🔗 Testing Basic Connection (FIXED)...');

        try {
            $token = env('WABLAS_TOKEN');
            $apiUrl = env('WABLAS_API_URL', 'https://texas.wablas.com/api');
            
            if (empty($token)) {
                $this->error('❌ Token not configured');
                return;
            }

            // FIXED: Test dengan format yang benar
            $headers = [
                'Authorization' => $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->get($apiUrl . '/device/status');

            $this->line("Connection test result:");
            $this->line("   Status Code: " . $response->status());
            $this->line("   Success: " . ($response->successful() ? '✅ Yes' : '❌ No'));
            
            if ($response->successful()) {
                $this->info('✅ Basic connection to Wablas v2 successful');
            } else {
                $this->error('❌ Connection failed');
                $this->line("   Response: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Connection test failed: ' . $e->getMessage());
        }
    }

    private function sendTestMessage()
    {
        $this->newLine();
        $this->info('📤 Sending Test Message (FIXED for Wablas v2)...');

        $phone = $this->option('phone') ?: env('APP_ADMIN_PHONE', '6282245454528');
        
        if (!$phone) {
            $this->error('❌ No phone number specified. Use --phone=62812345678 or set APP_ADMIN_PHONE in .env');
            return;
        }

        try {
            $token = env('WABLAS_TOKEN');
            $apiUrl = env('WABLAS_API_URL', 'https://texas.wablas.com/api');
            
            if (empty($token)) {
                $this->error('❌ Token not configured');
                return;
            }

            // FIXED: Format yang benar untuk Wablas v2
            $headers = [
                'Authorization' => $token,  // FIXED: Token saja
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ];

            $message = "🧪 Test message from Zafa Potato CRM (Wablas v2 FIXED)\n";
            $message .= "⏰ Time: " . now()->format('Y-m-d H:i:s') . "\n";
            $message .= "🔧 Source: Artisan Command\n";
            $message .= "✅ WhatsApp integration is working!";

            // FIXED: Payload format yang benar (tidak dalam wrapper "data")
            $payload = [
                'phone' => $phone,
                'message' => $message
            ];

            $this->line("Sending to: {$phone}");
            $this->line("Endpoint: {$apiUrl}/send-message");
            $this->line("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));

            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post($apiUrl . '/send-message', $payload);

            $this->line("Response Status: " . $response->status());
            $this->line("Response Body: " . $response->body());

            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['status']) && $responseData['status'] == true) {
                    $this->info('✅ Test message sent successfully');
                    
                    $messageId = $responseData['data']['id'] ?? $responseData['id'] ?? 'unknown';
                    $this->line("   Message ID: {$messageId}");
                } else {
                    $this->error('❌ Message not sent successfully');
                    $this->line("   Error: " . ($responseData['message'] ?? 'Unknown error'));
                }
            } else {
                $this->error('❌ Failed to send test message');
                $this->line("   HTTP Status: " . $response->status());
                $this->line("   Response: " . $response->body());
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
        }
    }

    private function testAllEndpoints()
    {
        $this->newLine();
        $this->info('🔍 Testing All Wablas v2 Endpoints...');

        $token = env('WABLAS_TOKEN');
        $apiUrl = env('WABLAS_API_URL', 'https://texas.wablas.com/api');
        
        if (empty($token)) {
            $this->error('❌ Token not configured');
            return;
        }

        $headers = [
            'Authorization' => $token,
            'Accept' => 'application/json'
        ];

        $endpoints = [
            'Device Status' => '/device/status',
            'Device Info' => '/device/info', 
            'Device' => '/device',
            'Status' => '/status',
            'QR Code' => '/device/qr'
        ];

        foreach ($endpoints as $name => $endpoint) {
            try {
                $this->line("Testing {$name} ({$endpoint})...");
                
                $response = Http::timeout(10)
                    ->withHeaders($headers)
                    ->get($apiUrl . $endpoint);

                $status = $response->successful() ? '✅ OK' : '❌ FAIL';
                $this->line("   {$status} - Status: {$response->status()}");
                
                if (!$response->successful()) {
                    $this->line("   Error: " . $response->body());
                }
                
            } catch (\Exception $e) {
                $this->line("   ❌ EXCEPTION - " . $e->getMessage());
            }
        }
    }
}
