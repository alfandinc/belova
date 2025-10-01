<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
// create request
$request = Illuminate\Http\Request::create('/', 'POST', ['bulan' => '2025-09']);
$response = $app->call('App\\Http\\Controllers\\HRD\\PrSlipGajiController@simulateKpiPreview', ['request' => $request]);
if ($response instanceof Illuminate\Http\JsonResponse) {
    echo $response->getContent();
} else {
    var_dump($response);
}


