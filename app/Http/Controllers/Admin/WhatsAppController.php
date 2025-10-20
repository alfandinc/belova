<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class WhatsAppController extends Controller
{
    // WhatsApp integration removed - controller disabled

    public function index()
    {
        // Show a simple form for admins to send a single WhatsApp message
        return view('admin.whatsapp.index');
    }

    public function flowsView()
    {
        return view('admin.whatsapp.flows');
    }

    public function scheduledView()
    {
        return view('admin.whatsapp.scheduled');
    }

    public function getStatus()
    {
        return response()->json(['status' => 'disabled']);
    }

    public function testMessage()
    {
        return response()->json(['success' => false, 'error' => 'WhatsApp integration disabled']);
    }

    public function send(Request $request, WhatsAppService $whatsappService)
    {
        $data = $request->validate([
            'number' => 'required|string',
            'message' => 'nullable|string',
            'session' => 'nullable|string',
        ]);

        $number = preg_replace('/[^0-9]/', '', $data['number']);
        $message = $data['message'] ?? '';

    $session = $data['session'] ?? null;
    $result = $whatsappService->sendMessage($number, $message, $session);

        if (is_array($result) && isset($result['success']) && $result['success']) {
            return redirect()->route('admin.whatsapp.index')->with('success', 'Message queued for sending.');
        }

        $error = is_array($result) && isset($result['error']) ? $result['error'] : 'Unknown error or service disabled.';
        Log::warning('WhatsApp send failed', ['number' => $number, 'error' => $error]);

        return redirect()->route('admin.whatsapp.index')->with('error', 'Failed to send message: ' . $error);
    }

    public function startService()
    {
        try {
            $servicePath = base_path('whatsapp-service');

            // Check if service is already running on port 3000
            $checkProcess = new Process(['netstat', '-an']);
            $checkProcess->run();

            if (strpos($checkProcess->getOutput(), ':3000') !== false) {
                return redirect()->route('admin.whatsapp.index')->with('warning', 'WhatsApp service appears to already be running on port 3000.');
            }

            // Check if node_modules exists
            if (!file_exists($servicePath . '/node_modules')) {
                return redirect()->route('admin.whatsapp.index')->with('error', 'Node modules not found. Please run "npm install" in the whatsapp-service directory first.');
            }

            $nodeExe = 'C:\\Program Files\\nodejs\\node.exe';
            if (!file_exists($nodeExe)) {
                $nodeExe = 'node'; // fallback to PATH
            }
            // Kill any existing node processes first
            $killProcess = new Process(['taskkill', '/F', '/IM', 'node.exe']);
            $killProcess->run();

            // Start node server.js in whatsapp-service directory
            // Log user and environment info before starting
            $currentUser = get_current_user();
            $envVars = getenv();
            Log::info('Starting WhatsApp service', [
                'user' => $currentUser,
                'cwd' => $servicePath,
                'env' => $envVars
            ]);

            // Try launching Node.js with a visible window using cmd.exe /c start
            // Ensure the process will auto-initialize existing sessions on start
            $envVars['WHATSAPP_AUTO_INIT'] = 'true';
            $envVars['WHATSAPP_MAX_SESSIONS'] = '20';
            // Propagate WhatsApp sync/poll config from Laravel .env to Node process so UI-started Node can poll DB
            $envVars['WHATSAPP_SYNC_TOKEN'] = env('WHATSAPP_SYNC_TOKEN', $envVars['WHATSAPP_SYNC_TOKEN'] ?? '');
            $envVars['WHATSAPP_LARAVEL_POLL_SECONDS'] = env('WHATSAPP_LARAVEL_POLL_SECONDS', $envVars['WHATSAPP_LARAVEL_POLL_SECONDS'] ?? '30');
            $envVars['WHATSAPP_LARAVEL_URL'] = env('WHATSAPP_LARAVEL_URL', $envVars['WHATSAPP_LARAVEL_URL'] ?? 'http://127.0.0.1:8000');

            $cmdLine = ['cmd.exe', '/c', 'start', 'node', 'server.js'];
            $startProcess = new Process($cmdLine, $servicePath, $envVars);
            $startProcess->start();
            sleep(10);

            // Check service health via HTTP request
            try {
                $health = \Illuminate\Support\Facades\Http::timeout(3)->get('http://127.0.0.1:3000/status');
                if ($health->successful() && $health->json('status') === 'ready') {
                    Log::info('WhatsApp service started and healthy');
                    return redirect()->route('admin.whatsapp.index')->with('success', 'WhatsApp service started successfully and is ready!');
                }
            } catch (\Exception $e) {
                Log::warning('WhatsApp service HTTP health check failed: ' . $e->getMessage());
            }

            // Check if it started successfully (look for LISTENING on 127.0.0.1:3000)
            $checkAgain = new Process(['netstat', '-an']);
            $checkAgain->run();
            $output = $checkAgain->getOutput();
            $found = false;
            foreach (explode("\n", $output) as $line) {
                if (preg_match('/TCP\s+127\.0\.0\.1:3000\s+.*LISTENING/i', $line)) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                Log::info('WhatsApp service started successfully');
                return redirect()->route('admin.whatsapp.index')->with('success', 'WhatsApp service started successfully! The service is now running on port 3000.');
            } else {
                // Try to get more info about what went wrong
                Log::warning('WhatsApp service did not start - port 3000 not listening');

                // Check Node.js version
                $nodeVersion = '';
                try {
                    $versionProcess = new Process(['node', '--version']);
                    $versionProcess->run();
                    $nodeVersion = trim($versionProcess->getOutput());
                } catch (\Exception $e) {
                    $nodeVersion = 'unknown';
                }

                if (strpos($nodeVersion, 'v22.') === 0) {
                    return redirect()->route('admin.whatsapp.index')->with('error',
                        'CRITICAL: Node.js v22 has crypto compatibility issues that prevent WhatsApp service from starting. '
                        . 'SOLUTION: Downgrade to Node.js v18 LTS from nodejs.org. '
                        . 'Current version: ' . $nodeVersion . '. See NODE_DOWNGRADE_GUIDE.md in whatsapp-service folder for detailed instructions.'
                    );
                } else {
                    // Log more details from the last start attempt
                    $error = $startProcess->getErrorOutput();
                    $output = $startProcess->getOutput();
                    $cmd = $startProcess->getCommandLine();
                    $cwd = $servicePath;
                    Log::error('WhatsApp service failed to start (port 3000 not listening)', [
                        'error' => $error,
                        'output' => $output,
                        'command' => $cmd,
                        'cwd' => $cwd,
                        'node_version' => $nodeVersion
                    ]);
                    return redirect()->route('admin.whatsapp.index')->with('error',
                        'Service failed to start. Node.js version: ' . $nodeVersion . '. '
                        . 'Error: ' . $error . ' Output: ' . $output . ' Command: ' . $cmd . ' CWD: ' . $cwd
                        . ' Please check: 1) Node.js compatibility, 2) Dependencies installed (npm install), 3) Port conflicts.'
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to start WhatsApp service: ' . $e->getMessage());
            return redirect()->route('admin.whatsapp.index')->with('error', 'Failed to start service: ' . $e->getMessage());
        }
    }

    public function stopService()
    {
        try {
            // First, try to kill processes using port 3000
            $netstatProcess = new Process(['netstat', '-ano']);
            $netstatProcess->run();
            
            if ($netstatProcess->isSuccessful()) {
                $output = $netstatProcess->getOutput();
                if (preg_match_all('/:3000\s+.*?\s+LISTENING\s+(\d+)/', $output, $matches)) {
                    foreach ($matches[1] as $pid) {
                        $killProcess = new Process(['taskkill', '/F', '/PID', $pid]);
                        $killProcess->run();
                    }
                }
            }
            
            // Also kill any node processes as backup
            $stopProcess = new Process(['taskkill', '/F', '/IM', 'node.exe']);
            $stopProcess->run();

            Log::info('WhatsApp service stop command executed');
            return redirect()->route('admin.whatsapp.index')->with('success', 'WhatsApp service stopped successfully. Port 3000 is now free.');
        } catch (\Exception $e) {
            Log::error('Failed to stop WhatsApp service: ' . $e->getMessage());
            return redirect()->route('admin.whatsapp.index')->with('error', 'Failed to stop service: ' . $e->getMessage());
        }
    }

    public function debug()
    {
        $servicePath = base_path('whatsapp-service');
        $debugInfo = [];
        
        // Check if service directory exists
        $debugInfo['service_path'] = $servicePath;
        $debugInfo['service_path_exists'] = file_exists($servicePath);
        
        // Check if node_modules exists
        $debugInfo['node_modules_exists'] = file_exists($servicePath . '/node_modules');
        
        // Check if server.js exists
        $debugInfo['server_js_exists'] = file_exists($servicePath . '/server.js');
        
        // Check Node.js version
        try {
            $nodeProcess = new Process(['node', '--version']);
            $nodeProcess->run();
            $debugInfo['node_version'] = $nodeProcess->isSuccessful() ? trim($nodeProcess->getOutput()) : 'Not found';
        } catch (\Exception $e) {
            $debugInfo['node_version'] = 'Error: ' . $e->getMessage();
        }
        
        // Check if port 3000 is in use
        try {
            $netstatProcess = new Process(['netstat', '-an']);
            $netstatProcess->run();
            $debugInfo['port_3000_in_use'] = strpos($netstatProcess->getOutput(), ':3000') !== false;
        } catch (\Exception $e) {
            $debugInfo['port_3000_check'] = 'Error: ' . $e->getMessage();
        }
        
        // Check Node.js installation path
        try {
            $nodePathProcess = new Process(['powershell', '-Command', 'Get-Command node -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source']);
            $nodePathProcess->run();
            $debugInfo['node_path'] = $nodePathProcess->isSuccessful() ? trim($nodePathProcess->getOutput()) : 'Not found in PATH';
        } catch (\Exception $e) {
            $debugInfo['node_path'] = 'Error finding path: ' . $e->getMessage();
        }

        // Check if batch file exists
        $debugInfo['batch_file_exists'] = file_exists($servicePath . '/start-service.bat');
        
        // Try to start node process and capture output with multiple crypto workarounds
        try {
            // First try with the batch file if it exists
            if (file_exists($servicePath . '/start-service.bat')) {
                $debugInfo['testing_method'] = 'Using batch file (recommended)';
                $testProcess = new Process(['cmd', '/c', $servicePath . '/start-service.bat'], $servicePath);
                $testProcess->setTimeout(10);
                $testProcess->run();
            } else {
                $debugInfo['testing_method'] = 'Using direct node command';
                // Try with multiple Node.js v22 workaround flags
                $env = [
                    'NODE_OPTIONS' => '--openssl-legacy-provider --no-experimental-fetch',
                    'NODE_NO_WARNINGS' => '1'
                ];
                $testProcess = new Process(['node', '--openssl-legacy-provider', '--no-experimental-fetch', 'server.js'], $servicePath, $env);
                $testProcess->setTimeout(10);
                $testProcess->run();
            }
            
            $debugInfo['test_start_success'] = $testProcess->isSuccessful();
            $debugInfo['test_start_output'] = $testProcess->getOutput();
            $debugInfo['test_start_error'] = $testProcess->getErrorOutput();
            $debugInfo['test_start_exit_code'] = $testProcess->getExitCode();
            
            // If batch file works, recommend using UI start service
            if ($testProcess->isSuccessful() && file_exists($servicePath . '/start-service.bat')) {
                $debugInfo['ui_start_should_work'] = 'Yes - batch file method will be used by UI';
            }
            
        } catch (\Exception $e) {
            $debugInfo['test_start_error'] = 'Exception: ' . $e->getMessage();
        }
        
        return response()->json($debugInfo, 200, [], JSON_PRETTY_PRINT);
    }
}