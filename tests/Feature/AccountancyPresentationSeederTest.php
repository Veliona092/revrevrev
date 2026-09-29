<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\MockBoard;
use App\Models\MockBoardAttempt;
use App\Models\Module;
use App\Models\QuizAttempt;
use Database\Seeders\AccountancyPresentationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountancyPresentationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountancy_presentation_seeder_populates_all_required_demo_data(): void
    {
        // Run the seeder
        $this->seed(AccountancyPresentationSeeder::class);

        // Verify Teacher
        $this->assertDatabaseHas('users', [
            'idnumber' => '23-9999',
            'role' => 'teacher',
            'program' => 'accountancy',
            'status' => 'approved',
        ]);

        // Verify 10 Students
        $studentIdNumbers = [
            '23-9991', '23-9992', '23-9993', '23-9994', '23-9995',
            '23-9996', '23-9997', '23-9998', '23-10000', '23-10001',
        ];

        foreach ($studentIdNumbers as $idnum) {
            $this->assertDatabaseHas('users', [
                'idnumber' => $idnum,
                'role' => 'student',
                'program' => 'accountancy',
                'status' => 'approved',
            ]);
        }

        // Verify Review Class
        $this->assertDatabaseHas('classes', [
            'code' => 'ACC201-2026',
            'program' => 'accountancy',
        ]);

        $class = ClassModel::where('code', 'ACC201-2026')->first();
        $this->assertNotNull($class);
        $this->assertCount(10, $class->students);

        // Verify 7 Modules (3 class modules + 4 mock board phase modules across 2 mock boards)
        $this->assertEquals(7, Module::where('class_id', $class->id)->count());
        $this->assertEquals(3, Module::where('class_id', $class->id)->where('is_mock_board', false)->count());
        $this->assertEquals(4, Module::where('class_id', $class->id)->where('is_mock_board', true)->count());

        // Verify Module Attempts
        $this->assertGreaterThan(0, QuizAttempt::count());

        // Verify Mock Boards & Phases (2 Mock Boards)
        $mockBoards = MockBoard::where('program', 'accountancy')->get();
        $this->assertCount(2, $mockBoards);

        foreach ($mockBoards as $mb) {
            $this->assertCount(2, $mb->phases);
            $this->assertEquals(20, MockBoardAttempt::where('mock_board_id', $mb->id)->count());
            $this->assertDatabaseHas('mock_board_statistics', [
                'mock_board_id' => $mb->id,
            ]);
        }

        // Verify Mock Board 2 has statistically significant ANOVA (p < 0.05)
        $mockBoard2 = MockBoard::where('title', '2026 CPALE Intensive Pre-Board Simulation (Batch 2)')->first();
        $this->assertNotNull($mockBoard2);
        $this->assertDatabaseHas('mock_board_statistics', [
            'mock_board_id' => $mockBoard2->id,
            'anova_significant' => true,
        ]);
    }
}
