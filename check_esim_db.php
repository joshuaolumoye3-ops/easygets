<?php
// Test script to check database content
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========== eSIM UPLOADS TABLE STRUCTURE ==========\n\n";

// Get table columns (MySQL)
$columns = DB::select("DESCRIBE esim_uploads");

foreach ($columns as $col) {
    $name = $col->Field ?? 'unknown';
    $type = $col->Type ?? 'unknown';
    echo "  • $name: $type\n";
}

echo "\n==================== SAMPLE DATA ====================\n\n";

$uploads = DB::table('esim_uploads')
    ->select('id', 'transaction_id', 'number', 'images', 'created_at')
    ->limit(5)
    ->get();

if ($uploads->isEmpty()) {
    echo "No eSIM uploads found in database.\n";
} else {
    foreach ($uploads as $upload) {
        echo "ID: {$upload->id}\n";
        echo "Transaction ID: {$upload->transaction_id}\n";
        echo "Number: {$upload->number}\n";
        echo "Images Raw: " . $upload->images . "\n";
        echo "Parsed Images:\n";
        $images = json_decode($upload->images, true);
        if (is_array($images)) {
            foreach ($images as $img) {
                echo "  - $img\n";
            }
        }
        echo "Created: {$upload->created_at}\n";
        echo "---\n";
    }
}

echo "\n";
