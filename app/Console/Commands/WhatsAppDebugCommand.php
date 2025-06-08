<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WablasService;
use Illuminate\Support\Facades\Config;

class WhatsAppDebugCommand extends Command
{
    protected $signature = 'whatsapp:debug 
                           {--check-config : Check configuration only}
                           {--check-device : Check device status only}
                           {--test-send : Send test message}
                           {--phone= : Phone number for test message}';

    protected $description = 'Debug WhatsApp integration with Texas Wablas';

    public function handle()
    {
        $this->info('🔍 Texas Wablas WhatsApp Debug Tool');
        $this->info('================================');

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

        // Default: run all checks
        $this->checkConfiguration();
        $this->checkDeviceStatus();
        
        return 0;
    }

    private function debugEnvironment()
    {
        $this->newLine();
        $this->info('🔧 Environment Debug:');
        
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
        $this->line("   services.wablas.secret_key: " . (config('services.wablas.secret_key') ? '✅ SET' : '❌ NOT SET'));
        $this->line("   services.wablas.device_id: " . (config('services.wablas.device_id') ?: '❌ NOT SET'));
    }

    private function checkConfiguration()
    {
        $this->newLine();
        $this->info('⚙️  Checking Configuration...');

        // Force reload configuration
        Config::set('services.wablas', [
            'api_url' => env('WABLAS_API_URL', 'https://texas.wablas.com/api'),
            'token' => env('WABLAS_TOKEN'),
            'secret_key' => env('WABLAS_SECRET_KEY'),
            'device_id' => env('WABLAS_DEVICE_ID'),
            'timeout' => env('WABLAS_TIMEOUT', 60),
        ]);

        $config = [
            'API URL' => config('services.wablas.api_url'),
            'Token' => config('services.wablas.token') ? 'SET (' . strlen(config('services.wablas.token')) . ' characters)' : 'NOT SET',
            'Secret Key' => config('services.wablas.secret_key') ? 'SET (' . strlen(config('services.wablas.secret_key')) . ' characters)' : 'NOT SET',
            'Device ID' => config('services.wablas.device_id') ?: 'NOT SET',
            'Timeout' => config('services.wablas.timeout') . ' seconds',
        ];

        $allConfigured = true;
        foreach ($config as $key => $value) {
            $status = (strpos($value, 'NOT SET') === false) ? '✅' : '❌';
            if ($status === '❌') $allConfigured = false;
            $this->line("   {$status} {$key}: {$value}");
        }

        if ($allConfigured) {
            $this->info('✅ All configurations are set');
            
            // Test manual authorization token building
            $token = config('services.wablas.token');
            $secretKey = config('services.wablas.secret_key');
            $authToken = $token . '.' . $secretKey;
            $this->info("🔐 Authorization Token Preview: " . substr($authToken, 0, 50) . '...');
            
        } else {
            $this->error('❌ Some configurations are missing');
            $this->info('💡 Check your .env file and make sure all WABLAS_* variables are set');
        }
    }

    private function checkDeviceStatus()
    {
        $this->newLine();
        $this->info('📱 Checking Device Status...');

        try {
            // Force create new instance with fresh config
            $wablasService = new WablasService();
            
            $result = $wablasService->getDeviceStatus();

            if ($result['success']) {
                $this->info('✅ Device status check successful');
                $this->line("   Connection: " . ($result['isConnected'] ? '🟢 Connected' : '🔴 Disconnected'));
                $this->line("   Status: " . ($result['status'] ?? 'unknown'));
                
                if (isset($result['response'])) {
                    $this->info('📊 Raw response:');
                    $this->line('   ' . json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            } else {
                $this->error('❌ Failed to check device status');
                $this->error('Error: ' . $result['error']);
                
                if (isset($result['debug'])) {
                    $this->info('🔍 Debug information:');
                    foreach ($result['debug'] as $key => $value) {
                        $this->line("   {$key}: " . (is_array($value) ? json_encode($value) : $value));
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
            $this->line('   File: ' . $e->getFile());
            $this->line('   Line: ' . $e->getLine());
        }
    }

    private function sendTestMessage()
    {
        $this->newLine();
        $this->info('📤 Sending Test Message...');

        $phone = $this->option('phone') ?: config('services.admin.phone', '6282245454528');
        
        if (!$phone) {
            $this->error('❌ No phone number specified. Use --phone=62812345678 or set APP_ADMIN_PHONE in .env');
            return;
        }

        try {
            $wablasService = new WablasService();
            
            $message = "🧪 Test message from Zafa Potato CRM\n";
            $message .= "⏰ Time: " . now()->format('Y-m-d H:i:s') . "\n";
            $message .= "🔧 Source: Artisan Command\n";
            $message .= "✅ WhatsApp integration is working!";

            $result = $wablasService->sendMessage($phone, $message);

            if ($result['success']) {
                $this->info('✅ Test message sent successfully');
                $this->line("   Phone: {$phone}");
                $this->line("   Message ID: " . ($result['message_id'] ?? 'N/A'));
            } else {
                $this->error('❌ Failed to send test message');
                $this->error('Error: ' . $result['error']);
            }
        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
        }
    }
}
