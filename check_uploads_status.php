<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========== CHECKING TRANSACTIONS & eSIM UPLOADS ==========\n\n";

echo "eSIM Type Transactions:\n";
$esimTxns = DB::table('transactions')
    ->where('type', 'esim')
    ->select('id', 'reference', 'type', 'recipient', 'status', 'created_at')
    ->limit(10)
    ->get();

if ($esimTxns->isEmpty()) {
    echo "  ❌ No eSIM transactions found.\n";
} else {
    echo "  ✓ Found " . count($esimTxns) . " eSIM transactions\n";
    foreach ($esimTxns as $txn) {
        echo "    - ID {$txn->id}: {$txn->recipient} ({$txn->status})\n";
    }
}

echo "\n\neSIM Uploads in Database:\n";
$uploads = DB::table('esim_uploads')
    ->select('id', 'transaction_id', 'number', 'images')
    ->limit(10)
    ->get();

if ($uploads->isEmpty()) {
    echo "  ❌ No eSIM uploads found in esim_uploads table.\n";
} else {
    echo "  ✓ Found " . count($uploads) . " eSIM uploads\n";
    foreach ($uploads as $up) {
        echo "    - ID {$up->id}, Txn {$up->transaction_id}: {$up->images}\n";
    }
}

echo "\n\nImages in Public Folder:\n";
$publicImages = glob(__DIR__ . '/public/uploads/esim/*');
if (empty($publicImages)) {
    echo "  ❌ No images in public/uploads/esim/\n";
} else {
    echo "  ✓ Found " . count($publicImages) . " files in public/uploads/esim/\n";
    foreach (array_slice($publicImages, 0, 5) as $img) {
        echo "    - " . basename($img) . "\n";
    }
    if (count($publicImages) > 5) {
        echo "    ... and " . (count($publicImages) - 5) . " more\n";
    }
}

echo "\n========== ISSUE SUMMARY ==========\n";
echo "Database:\n";
echo "  - Transactions: " . (count($esimTxns) > 0 ? "✓" : "✗") . "\n";
echo "  - eSIM Uploads: " . (count($uploads) > 0 ? "✓" : "✗") . "\n";
echo "  - Images stored in DB: " . (count($uploads) > 0 && !empty($uploads[0]->images) ? "✓" : "✗") . "\n";
echo "\nFile System:\n";
echo "  - Images in public/uploads/esim/: " . (count($publicImages) > 0 ? "✓" : "✗") . "\n";
echo "\n";
