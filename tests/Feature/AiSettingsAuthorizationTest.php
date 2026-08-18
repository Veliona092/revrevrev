<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Models\ClassModel;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AiSettingsAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_update_global_ai_settings(): void
    {
        $superadmin = $this->createUser([
            'role' => 'superadmin',
            'program' => null,
        ]);

        $response = $this->actingAs($superadmin)->postJson(route('superadmin.ai-settings.update'), [
            'feature' => [
                'quiz_generation_enabled' => false,
            ],
        ]);

        $response->assertOk();

        $setting = AiSetting::query()->where('key', 'feature.quiz_generation_enabled')->first();
        $this->assertNotNull($setting);
        $this->assertFalse(json_decode((string) $setting->value, true));
    }

    public function test_admin_cannot_update_global_ai_settings(): void
    {
        $admin = $this->createUser([
            'role' => 'admin',
            'program' => null,
        ]);

        $this->actingAs($admin)
            ->postJson(route('superadmin.ai-settings.update'), [
                'feature' => [
                    'quiz_generation_enabled' => false,
                ],
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_class_ai_settings(): void
    {
        $admin = $this->createUser([
            'role' => 'admin',
            'program' => null,
        ]);

        $teacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);

        $class = ClassModel::query()->create([
            'name' => 'AI Settings Class',
            'code' => 'AI-SET-100',
            'school_year' => now()->year,
            'description' => 'Class for AI settings tests',
            'created_by' => $teacher->id,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.class-ai-settings.update', $class), [
            'features' => [
                'quiz_generation_enabled' => false,
            ],
            'quiz_defaults' => [
                'question_count' => 7,
                'difficulty' => 'Hard',
            ],
        ]);

        $response->assertOk();

        $class->refresh();
        $this->assertFalse((bool) data_get($class->ai_settings, 'features.quiz_generation_enabled'));
        $this->assertSame(7, (int) data_get($class->ai_settings, 'quiz_defaults.question_count'));
        $this->assertSame('Hard', (string) data_get($class->ai_settings, 'quiz_defaults.difficulty'));
    }

    public function test_quiz_insights_returns_403_when_class_feature_is_disabled(): void
    {
        $teacher = $this->createUser([
            'role' => 'teacher',
            'program' => 'teacher',
        ]);

        $student = $this->createUser([
            'role' => 'student',
            'program' => 'educ',
        ]);

        $class = ClassModel::query()->create([
            'name' => 'Disabled AI Insights',
            'code' => 'AI-SET-200',
            'school_year' => now()->year,
            'description' => 'Class with AI insights disabled',
            'created_by' => $teacher->id,
            'ai_settings' => [
                'features' => [
                    'quiz_generation_enabled' => true,
                    'quiz_insights_enabled' => false,
                    'class_summary_enabled' => true,
                ],
                'quiz_defaults' => [
                    'question_count' => 10,
                    'difficulty' => 'Normal',
                ],
            ],
        ]);

        $module = Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Sample Quiz Module',
            'description' => 'Quiz module for AI insights gating test',
            'is_quiz' => true,
        ]);

        $this->actingAs($student)
            ->postJson(route('quiz.insights', $module))
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    private function createUser(array $overrides = []): User
    {
        static $counter = 5000;
        $counter++;

        return User::query()->create(array_merge([
            'idnumber' => 'ID'.$counter,
            'name' => 'User '.$counter,
            'email' => 'user'.$counter.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'status' => 'active',
            'program' => 'educ',
            'program_locked' => false,
            'email_verified_at' => now(),
        ], $overrides));
    }
}
