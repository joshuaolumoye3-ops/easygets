<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "\n========== eSIM IMAGE VERIFICATION ==========\n\n";

$uploads = DB::table('esim_uploads')
    ->select('id', 'transaction_id', 'images', 'number')
    ->limit(5)
    ->get();

if ($uploads->isEmpty()) {
    echo "No eSIM uploads found.\n";
    exit;
}

echo "Testing image URL generation for " . count($uploads) . " uploads:\n\n";

foreach ($uploads as $upload) {
    echo "Upload ID {$upload->id} (Txn {$upload->transaction_id}, Number: {$upload->number})\n";
    echo "Raw JSON: " . substr($upload->images, 0, 100) . "...\n";
    
    // Decode like the model does
    $images = json_decode($upload->images, true) ?? [];
    
    echo "Decoded paths (" . count($images) . "):\n";
    foreach ($images as $img) {
        // Normalize
        $img = str_replace('\\/', '/', $img);
        $img = ltrim($img, '/');
        
        echo "  Raw: $img\n";
        
        // Generate URL
        if (strpos($img, 'storage/') === 0) {
            $actualPath = str_replace('storage/', '', $img);
            $url = Storage::disk('public')->url($actualPath);
            $fileExists = file_exists(storage_path('app/public/' . $actualPath));
            echo "    → Storage: $url (exists: " . ($fileExists ? 'YES' : 'NO') . ")\n";
        } else if (strpos($img, 'uploads/') === 0) {
            $url = url('/' . $img);
            $fileExists = file_exists(public_path($img));
            echo "    → Public: $url (exists: " . ($fileExists ? 'YES' : 'NO') . ")\n";
        } else {
            echo "    → Unknown format\n";
        }
    }
    echo "\n";
}

echo "✓ Verification complete!\n";
echo "\nIf all files show 'YES' for exists, images should display correctly.\n";
echo "If any show 'NO', the file is missing or path is incorrect.\n\n";
