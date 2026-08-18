<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MOCK BOARDS (id, title, program) ===\n";
$boards = App\Models\MockBoard::all(['id', 'title', 'program']);
foreach ($boards as $b) {
    echo "id={$b->id} | title={$b->title} | program=[{$b->program}]\n";
}

echo "\n=== MOCK BOARD PHASES (all) ===\n";
$phases = App\Models\MockBoardPhase::all(['id', 'mock_board_id', 'phase_type', 'module_id']);
foreach ($phases as $p) {
    echo "id={$p->id} | mock_board_id={$p->mock_board_id} | phase_type={$p->phase_type} | module_id={$p->module_id}\n";
}
if ($phases->isEmpty()) {
    echo "(walang laman)\n";
}