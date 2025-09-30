<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// bootstrap kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Finance\Billing;

$rows = Billing::onlyTrashed()->take(200)->get();
if ($rows->isEmpty()) {
    echo "NO_TRASHED_BILLINGS\n";
    exit(0);
}

foreach ($rows as $b) {
    echo implode("\t", [
        $b->id,
        $b->visitation_id,
        $b->deleted_at ? $b->deleted_at->toDateTimeString() : 'null',
        isset($b->nama_item) ? $b->nama_item : '',
        isset($b->jumlah) ? $b->jumlah : ''
    ]) . "\n";
}
