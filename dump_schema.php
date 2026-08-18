<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dbName = env('DB_DATABASE');
$tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
$key = "Tables_in_{$dbName}";

foreach ($tables as $table) {
    $tableName = $table->$key;
    echo "=== TABLE: {$tableName} ===\n";
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
    foreach ($columns as $col) {
        echo "  - {$col}\n";
    }
    echo "\n";
}