<?php

// Temporary script to adjust stock for testing
// Usage: php storage/tmp_adjust_stock.php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Inventory\Barang;
use App\Models\Inventory\StokBarang;

$search = '%lampu duduk kuning%';
$barang = Barang::where('name', 'like', $search)->first();
if (! $barang) {
    echo "Barang not found matching: $search\n";
    exit(1);
}

echo "Found barang: id={$barang->id} name={$barang->name}\n";
$current = optional($barang->stokBarang)->jumlah ?: 0;
echo "Current stok: $current\n";

$change = 5; // increase by 5

$kartu = StokBarang::adjustStock($barang->id, $change, 'assistant test', 'assistant.script', null, 1);
if ($kartu) {
    echo "Kartu stok created: id={$kartu->id}\n";
    print_r($kartu->toArray());
} else {
    echo "No kartu stok returned.\n";
}

echo "Done.\n";