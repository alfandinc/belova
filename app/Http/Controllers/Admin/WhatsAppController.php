<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    protected $whatsappService;

    public function __construct()
    {
        $this->whatsappService = new WhatsAppService();
    }

    /**
     * Display WhatsApp management page
     */
    public function index()
    {
        $status = $this->whatsappService->getServiceHealth();
        $connected = $this->whatsappService->isConnected();
        
        return view('admin.whatsapp.index', compact('status', 'connected'));
    }

    /**
     * Get WhatsApp service status (AJAX)
     */
    public function getStatus()
    {
        try {
            $status = $this->whatsappService->getServiceHealth();
            $connected = $this->whatsappService->isConnected();
            
            return response()->json([
                'status' => $status,
                'connected' => $connected,
                'service_url' => config('whatsapp.service_url'),
                'enabled' => config('whatsapp.enabled'),
                'debug' => [
                    'timestamp' => now()->toISOString(),
                    'user' => Auth::user() ? Auth::user()->name : 'Guest'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp status check failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => null,
                'connected' => false,
                'service_url' => config('whatsapp.service_url'),
                'enabled' => config('whatsapp.enabled'),
                'error' => $e->getMessage(),
                'debug' => [
                    'timestamp' => now()->toISOString(),
                    'user' => Auth::user() ? Auth::user()->name : 'Guest'
                ]
            ]);
        }
    }

    /**
     * Get QR code for WhatsApp authentication
     */
    public function getQrCode()
    {
        try {
            $response = $this->whatsappService->getServiceHealth();
            
            if ($response['status'] !== 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp service is not running'
                ]);
            }

            // Check if already connected
            if ($this->whatsappService->isConnected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp is already connected'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Check WhatsApp service logs for QR code',
                'note' => 'QR code will appear in the service output. Please restart the service to generate a new QR code.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test WhatsApp message sending
     */
    public function testMessage(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string|max:1000'
        ]);

        try {
            if (!$this->whatsappService->isConnected()) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp service is not connected'
                ]);
            }

            $result = $this->whatsappService->sendMessage(
                $request->phone,
                $request->message
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update WhatsApp settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'service_url' => 'required|url',
            'template_visitation' => 'required|string|max:4000'
        ]);

        try {
            // Update .env file (simplified approach)
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            $envContent = preg_replace(
                '/WHATSAPP_ENABLED=.*/',
                'WHATSAPP_ENABLED=' . ($request->enabled ? 'true' : 'false'),
                $envContent
            );

            $envContent = preg_replace(
                '/WHATSAPP_SERVICE_URL=.*/',
                'WHATSAPP_SERVICE_URL=' . $request->service_url,
                $envContent
            );

            // Update or add template setting
            $templateValue = str_replace(["\r\n", "\n", "\r"], "\\n", $request->template_visitation);
            $templateValue = str_replace('"', '\"', $templateValue);
            
            if (strpos($envContent, 'WHATSAPP_TEMPLATE_VISITATION=') !== false) {
                $envContent = preg_replace(
                    '/WHATSAPP_TEMPLATE_VISITATION=.*/',
                    'WHATSAPP_TEMPLATE_VISITATION="' . $templateValue . '"',
                    $envContent
                );
            } else {
                $envContent .= "\nWHATSAPP_TEMPLATE_VISITATION=\"" . $templateValue . "\"\n";
            }

            file_put_contents($envPath, $envContent);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating settings: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Start WhatsApp service
     */
    public function startService()
    {
        try {
            // Check if service is already running
            if ($this->checkServiceHealth()) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp service is already running'
                ]);
            }
            
            // Start the service
            $result = $this->startWhatsAppService();
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp service started successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start WhatsApp service',
                    'error' => $result['error'] ?? 'Unknown error',
                    'instructions' => [
                        'Open a new terminal/command prompt',
                        'Navigate to: C:\wamp64\www\belova\whatsapp-service',
                        'Run: node index.js',
                        'Keep the terminal window open'
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp service start failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Service start failed: ' . $e->getMessage(),
                'instructions' => [
                    'Manual start required:',
                    '1. Open a new terminal/command prompt',
                    '2. Navigate to: C:\wamp64\www\belova\whatsapp-service',
                    '3. Run: node index.js',
                    '4. Keep the terminal window open'
                ]
            ]);
        }
    }

    /**
     * Restart WhatsApp service
     */
    public function restartService()
    {
        try {
            // First, try to stop any existing node processes
            $this->stopWhatsAppService();
            
            // Wait a moment
            sleep(2);
            
            // Start the service
            $result = $this->startWhatsAppService();
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp service restarted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to start WhatsApp service',
                    'error' => $result['error'] ?? 'Unknown error',
                    'instructions' => [
                        'Open a new terminal/command prompt',
                        'Navigate to: C:\wamp64\www\belova\whatsapp-service',
                        'Run: node index.js',
                        'Keep the terminal window open'
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp service restart failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Service restart failed: ' . $e->getMessage(),
                'instructions' => [
                    'Manual restart required:',
                    '1. Open a new terminal/command prompt',
                    '2. Navigate to: C:\wamp64\www\belova\whatsapp-service',
                    '3. Run: node index.js',
                    '4. Keep the terminal window open'
                ]
            ]);
        }
    }

    /**
     * Start WhatsApp service
     */
    private function startWhatsAppService()
    {
        try {
            $servicePath = base_path('whatsapp-service');
            $indexFile = $servicePath . DIRECTORY_SEPARATOR . 'index.js';
            
            Log::info('Starting WhatsApp service', [
                'service_path' => $servicePath,
                'index_file' => $indexFile,
                'file_exists' => file_exists($indexFile),
                'os_family' => PHP_OS_FAMILY
            ]);
            
            if (!file_exists($indexFile)) {
                Log::error('WhatsApp service index.js not found', ['path' => $indexFile]);
                return [
                    'success' => false,
                    'error' => 'WhatsApp service files not found at: ' . $indexFile
                ];
            }
            
            // For Windows, use a simpler approach
            if (PHP_OS_FAMILY === 'Windows') {
                // Use full path to node.exe to avoid PATH issues
                $nodePath = 'C:\\Program Files\\nodejs\\node.exe';
                $command = 'powershell -Command "Start-Process -FilePath \\"' . $nodePath . '\\" -ArgumentList index.js -WorkingDirectory \\"' . $servicePath . '\\" -WindowStyle Minimized"';
                Log::info('Executing command', ['command' => $command]);
                
                $result = shell_exec($command . ' 2>&1');
                Log::info('Command result', ['result' => $result]);
                
                // Wait a moment for service to start
                sleep(5);
                
                // Check if service is responding
                $healthCheck = $this->checkServiceHealth();
                Log::info('Health check result', ['health' => $healthCheck]);
                
                if ($healthCheck) {
                    return ['success' => true];
                } else {
                    // Try alternative command if first one failed
                    Log::info('First attempt failed, trying alternative command');
                    $altCommand = 'start /min powershell -Command "cd \\"' . $servicePath . '\\"; node index.js"';
                    Log::info('Alternative command', ['command' => $altCommand]);
                    
                    shell_exec($altCommand);
                    sleep(5);
                    
                    if ($this->checkServiceHealth()) {
                        return ['success' => true];
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Service started but not responding after 10 seconds'
                        ];
                    }
                }
            } else {
                // For Linux/Mac
                $command = 'cd "' . $servicePath . '" && nohup node index.js > /dev/null 2>&1 & echo $!';
                Log::info('Executing Unix command', ['command' => $command]);
                
                $pid = shell_exec($command);
                
                if ($pid) {
                    sleep(3);
                    return ['success' => $this->checkServiceHealth()];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Failed to start service process'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Exception starting WhatsApp service', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Stop WhatsApp service
     */
    private function stopWhatsAppService()
    {
        try {
            Log::info('Attempting to stop WhatsApp service gracefully');
            
            // First try graceful shutdown via API endpoint
            try {
                $response = Http::timeout(10)->post(config('whatsapp.service_url') . '/shutdown');
                
                if ($response->successful()) {
                    Log::info('Graceful shutdown request sent successfully');
                    
                    // Wait a bit for the service to shut down
                    sleep(3);
                    
                    // Check if service is actually stopped
                    $isStillRunning = $this->checkServiceHealth();
                    
                    if (!$isStillRunning) {
                        Log::info('Service stopped gracefully');
                        return true;
                    } else {
                        Log::warning('Service still running after graceful shutdown attempt');
                    }
                } else {
                    Log::warning('Graceful shutdown request failed', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Graceful shutdown failed, trying force kill', ['error' => $e->getMessage()]);
            }
            
            // If graceful shutdown failed, try force kill as fallback
            if (PHP_OS_FAMILY === 'Windows') {
                Log::info('Attempting force kill as fallback');
                
                // Try to find and kill node processes on port 3000
                exec('netstat -ano | findstr :3000', $netstatOutput);
                
                if (!empty($netstatOutput)) {
                    foreach ($netstatOutput as $line) {
                        if (strpos($line, 'LISTENING') !== false) {
                            preg_match('/\s+(\d+)$/', $line, $matches);
                            if (isset($matches[1])) {
                                $pid = $matches[1];
                                Log::info('Found process on port 3000', ['pid' => $pid]);
                                
                                // Try to kill the specific PID
                                exec("taskkill /F /PID $pid 2>NUL", $killOutput, $killCode);
                                Log::info('Force kill attempt', ['pid' => $pid, 'code' => $killCode]);
                            }
                        }
                    }
                }
            } else {
                // For Linux/Mac
                shell_exec('pkill -f "node.*index.js"');
            }
            
            sleep(2); // Give it time to stop
            
            // Final verification
            $isRunning = $this->checkServiceHealth();
            Log::info('Final service health check after stop', ['running' => $isRunning]);
            
            return !$isRunning;
        } catch (\Exception $e) {
            Log::error('Failed to stop WhatsApp service: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Stop WhatsApp service
     */
    public function stopService()
    {
        try {
            $result = $this->stopWhatsAppService();
            
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp service stopped successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to stop WhatsApp service completely'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp service stop failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Service stop failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Check if service is responding
     */
    private function checkServiceHealth()
    {
        try {
            $url = config('whatsapp.service_url') . '/health';
            Log::info('Checking service health', ['url' => $url]);
            
            $response = Http::timeout(5)->get($url);
            $isHealthy = $response->successful();
            
            Log::info('Health check response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'successful' => $isHealthy
            ]);
            
            return $isHealthy;
        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'error' => $e->getMessage(),
                'url' => config('whatsapp.service_url') . '/health'
            ]);
            return false;
        }
    }
}