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
            'code' => 'ACC401-2026',
            'program' => 'accountancy',
        ]);

        $class = ClassModel::where('code', 'ACC401-2026')->first();
        $this->assertNotNull($class);
        $this->assertCount(10, $class->students);

        // Verify 5 Modules (3 class modules + 2 mock board phase modules)
        $this->assertEquals(5, Module::where('class_id', $class->id)->count());
        $this->assertEquals(3, Module::where('class_id', $class->id)->where('is_mock_board', false)->count());
        $this->assertEquals(2, Module::where('class_id', $class->id)->where('is_mock_board', true)->count());

        // Verify Module Attempts
        $this->assertGreaterThan(0, QuizAttempt::count());

        // Verify Mock Board & Phases
        $mockBoard = MockBoard::where('program', 'accountancy')->first();
        $this->assertNotNull($mockBoard);
        $this->assertCount(2, $mockBoard->phases);

        // Verify Mock Board Attempts
        $this->assertEquals(20, MockBoardAttempt::where('mock_board_id', $mockBoard->id)->count());

        // Verify ANOVA / Mock Board Statistics were generated
        $this->assertDatabaseHas('mock_board_statistics', [
            'mock_board_id' => $mockBoard->id,
        ]);
    }
}
