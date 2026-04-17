<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========== DATABASE TABLES ==========\n\n";

// Get all tables
$tables = DB::select("SHOW TABLES");

$tableNames = [];
foreach ($tables as $table) {
    foreach ($table as $name) {
        $tableNames[] = $name;
    }
}

foreach ($tableNames as $table) {
    $count = DB::table($table)->count();
    echo "{$table}: {$count} rows\n";
}

echo "\n========== KEY TABLES FOR eSIM ==========\n";
echo "\nusers: " . DB::table('users')->count() . " rows\n";
echo "transactions: " . DB::table('transactions')->count() . " rows\n";
echo "esim_uploads: " . DB::table('esim_uploads')->count() . " rows\n";
echo "esim_countries: " . DB::table('esim_countries')->count() . " rows\n";

echo "\n";
