<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ERM\Visitation;

echo "Checking visitation data...\n";

$visitation_id = '202510101859146296370';
echo "Looking for visitation ID: $visitation_id\n";

$visitation = Visitation::find($visitation_id);

if ($visitation) {
    echo "✅ Visitation found!\n";
    echo "ID: {$visitation->id}\n";
    echo "Patient ID: {$visitation->pasien_id}\n";
    echo "Date: {$visitation->tanggal_visitation}\n";
    echo "Status: {$visitation->status}\n";
    
    // Check if patient exists
    if ($visitation->pasien) {
        echo "Patient: {$visitation->pasien->nama}\n";
        echo "Phone: {$visitation->pasien->no_hp}\n";
    } else {
        echo "❌ Patient not found!\n";
    }
} else {
    echo "❌ Visitation not found!\n";
    
    // Let's check what visitations exist
    echo "\nChecking recent visitations...\n";
    $recent = Visitation::orderBy('created_at', 'desc')->limit(5)->get();
    foreach ($recent as $v) {
        echo "ID: {$v->id}, Patient: {$v->pasien_id}, Date: {$v->tanggal_visitation}\n";
    }
}