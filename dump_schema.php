<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$dbName = env('DB_DATABASE');
$tables = DB::select('SHOW TABLES');
$key = "Tables_in_{$dbName}";

foreach ($tables as $table) {
    $tableName = $table->$key;
    echo "=== TABLE: {$tableName} ===\n";
    $columns = Schema::getColumnListing($tableName);
    foreach ($columns as $col) {
        echo "  - {$col}\n";
    }
    echo "\n";
}
