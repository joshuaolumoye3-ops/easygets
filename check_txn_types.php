<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========== ALL TRANSACTION TYPES ==========\n\n";

$types = DB::table('transactions')
    ->select(DB::raw('type, COUNT(*) as count'))
    ->groupBy('type')
    ->orderByRaw('count DESC')
    ->get();

if ($types->isEmpty()) {
    echo "No transactions in database!\n";
} else {
    foreach ($types as $t) {
        echo "{$t->type}: {$t->count} transactions\n";
    }
}

echo "\n";
