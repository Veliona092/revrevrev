<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== MOCK BOARD ATTEMPTS ===\n";
$attempts = App\Models\MockBoardAttempt::all();
foreach ($attempts as $a) {
    echo "id={$a->id} | user_id={$a->user_id} | mock_board_id={$a->mock_board_id} | phase_type=[{$a->phase_type}] | percentage={$a->percentage} | passed=" . ($a->passed ? 'true' : 'false') . "\n";
}
if ($attempts->isEmpty()) {
    echo "(walang laman)\n";
}

echo "\n=== MOCK BOARDS (id + program) ===\n";
$boards = App\Models\MockBoard::all(['id', 'title', 'program', 'class_id', 'teacher_id']);
foreach ($boards as $b) {
    echo "id={$b->id} | title={$b->title} | program=[{$b->program}] | class_id=" . ($b->class_id ?? 'NULL') . " | teacher_id={$b->teacher_id}\n";
}

echo "\n=== MOCK BOARD PHASES ===\n";
$phases = App\Models\MockBoardPhase::all(