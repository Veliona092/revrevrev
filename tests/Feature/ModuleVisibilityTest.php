<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModuleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 9000;

    private function createUser(array $overrides = []): User
    {
        $n = ++self::$counter;

        return User::query()->create(array_merge([
            'idnumber' => 'VIS'.$n,
            'name' => 'VisUser '.$n,
            'email' => 'vis'.$n.'@example.test',
            'password' => Hash::make('password'),
            'role' => 'student',
            'status' => 'active',
            'program' => 'accountancy',
            'program_locked' => false,
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function createClass(User $teacher): ClassModel
    {
        static $cc = 900;
        $cc++;

        return ClassModel::query()->create([
            'name' => 'Vis Class '.$cc,
            'code' => 'VC'.$cc,
            'created_by' => $teacher->id,
        ]);
    }

    private function createModule(ClassModel $class, array $overrides = []): Module
    {
        return Module::query()->create(array_merge([
            'class_id' => $class->id,
            'title' => 'Vis Module '.uniqid(),
            'is_quiz' => false,
            'is_assignment' => false,
            'visibility' => 'all',
        ], $overrides));
    }

    // ── searchClassStudents ──────────────────────────────────────────────

    public function test_teacher_can_search_enrolled_students_by_name(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($teacher);
        $student = $this->createUser(['name' => 'Alice Wonderland']);
        $student->classes()->attach($class->id);

        $this->actingAs($teacher)
            ->getJson(route('classes.students.search', $class).'?q=Alice')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Alice Wonderland']);
    }

    public function test_student_search_matches_idnumber(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($teacher);
        $student = $this->createUser(['idnumber' => 'UNIQ8877']);
        $student->classes()->attach($class->id);

        $this->actingAs($teacher)
            ->getJson(route('classes.students.search', $class).'?q=UNIQ8877')
            ->assertOk()
            ->assertJsonFragment(['idnumber' => 'UNIQ8877']);
    }

    public function test_student_search_returns_required_fields(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($teacher);
        $student = $this->createUser(['program' => 'accountancy']);
        $student->classes()->attach($class->id);

        $this->actingAs($teacher)
            ->getJson(route('classes.students.search', $class).'?q='.urlencode($student->name))
            ->assertOk()
            ->assertJsonStructure([['id', 'name', 'email', 'idnumber', 'program']]);
    }

    public function test_non_owner_teacher_cannot_search_students(): void
    {
        $owner = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $intruder = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($owner);

        $this->actingAs($intruder)
            ->getJson(route('classes.students.search', $class))
            ->assertForbidden();
    }

    public function test_unenrolled_student_does_not_appear_in_search(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($teacher);
        $outsider = $this->createUser(['name' => 'Outside Bob']);
        // $outsider is NOT enrolled in $class

        $this->actingAs($teacher)
            ->getJson(route('classes.students.search', $class).'?q=Outside')
            ->assertOk()
            ->assertJsonMissing(['id' => $outsider->id]);
    }

    // ── storeModule visibility ───────────────────────────────────────────

    public function test_module_defaults_to_all_visibility(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($teacher);

        $this->actingAs($teacher)
            ->post(route('classes.modules.store', $class), [
                'class_id' => $class->id,
                'type' => 'quiz',
                'title' => 'Default Visibility Quiz',
            ]);

        $this->assertDatabaseHas('modules', [
            'title' => 'Default Visibility Quiz',
            'visibility' => 'all',
        ]);
    }

    public function test_module_created_with_selected_visibility_syncs_enrolled_users(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($teacher);
        $student = $this->createUser();
        $student->classes()->attach($class->id);

        $this->actingAs($teacher)
            ->post(route('classes.modules.store', $class), [
                'class_id' => $class->id,
                'type' => 'quiz',
                'title' => 'Selected Quiz',
                'visibility' => 'selected',
                'visible_user_ids' => [$student->id],
            ]);

        $module = Module::query()->where('title', 'Selected Quiz')->firstOrFail();

        $this->assertEquals('selected', $module->visibility);
        $this->assertDatabaseHas('module_user_visibility', [
            'module_id' => $module->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_non_enrolled_user_id_is_excluded_from_selected_visibility(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($teacher);
        $enrolled = $this->createUser();
        $outsider = $this->createUser();
        $enrolled->classes()->attach($class->id);
        // $outsider is NOT enrolled

        $this->actingAs($teacher)
            ->post(route('classes.modules.store', $class), [
                'class_id' => $class->id,
                'type' => 'quiz',
                'title' => 'Intersection Quiz',
                'visibility' => 'selected',
                'visible_user_ids' => [$enrolled->id, $outsider->id],
            ]);

        $module = Module::query()->where('title', 'Intersection Quiz')->firstOrFail();

        $this->assertDatabaseHas('module_user_visibility', ['module_id' => $module->id, 'user_id' => $enrolled->id]);
        $this->assertDatabaseMissing('module_user_visibility', ['module_id' => $module->id, 'user_id' => $outsider->id]);
    }

    public function test_module_created_with_except_visibility_syncs_excluded_users(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($teacher);
        $excluded = $this->createUser();
        $excluded->classes()->attach($class->id);

        $this->actingAs($teacher)
            ->post(route('classes.modules.store', $class), [
                'class_id' => $class->id,
                'type' => 'quiz',
                'title' => 'Except Visibility Quiz',
                'visibility' => 'except',
                'visible_user_ids' => [$excluded->id],
            ]);

        $module = Module::query()->where('title', 'Except Visibility Quiz')->firstOrFail();

        $this->assertDatabaseHas('modules', ['id' => $module->id, 'visibility' => 'except']);
        $this->assertDatabaseHas('module_user_visibility', [
            'module_id' => $module->id,
            'user_id' => $excluded->id,
        ]);
    }

    public function test_invalid_visibility_value_is_rejected(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $class = $this->createClass($teacher);

        $this->actingAs($teacher)
            ->post(route('classes.modules.store', $class), [
                'class_id' => $class->id,
                'type' => 'quiz',
                'title' => 'Bad Visibility',
                'visibility' => 'nobody',
            ])
            ->assertSessionHasErrors('visibility');
    }

    // ── studentModules visibility filtering ─────────────────────────────

    public function test_student_sees_module_with_all_visibility(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $student = $this->createUser();
        $class = $this->createClass($teacher);
        $student->classes()->attach($class->id);

        $this->createModule($class, ['title' => 'All-Public Module', 'visibility' => 'all']);

        $this->actingAs($student)
            ->get(route('student.modules', $class))
            ->assertOk()
            ->assertSee('All-Public Module');
    }

    public function test_student_sees_selected_module_when_in_visible_list(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $student = $this->createUser();
        $class = $this->createClass($teacher);
        $student->classes()->attach($class->id);

        $module = $this->createModule($class, ['title' => 'Selected For Me', 'visibility' => 'selected']);
        $module->visibleTo()->attach($student->id);

        $this->actingAs($student)
            ->get(route('student.modules', $class))
            ->assertOk()
            ->assertSee('Selected For Me');
    }

    public function test_student_cannot_see_selected_module_when_not_in_visible_list(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $studentA = $this->createUser();
        $studentB = $this->createUser();
        $class = $this->createClass($teacher);
        $studentA->classes()->attach($class->id);
        $studentB->classes()->attach($class->id);

        $module = $this->createModule($class, ['title' => 'Selected Only For A', 'visibility' => 'selected']);
        $module->visibleTo()->attach($studentA->id);

        $this->actingAs($studentB)
            ->get(route('student.modules', $class))
            ->assertOk()
            ->assertDontSee('Selected Only For A');
    }

    public function test_student_cannot_see_except_module_when_they_are_excluded(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $student = $this->createUser();
        $class = $this->createClass($teacher);
        $student->classes()->attach($class->id);

        $module = $this->createModule($class, ['title' => 'Excluded From Me', 'visibility' => 'except']);
        $module->visibleTo()->attach($student->id);

        $this->actingAs($student)
            ->get(route('student.modules', $class))
            ->assertOk()
            ->assertDontSee('Excluded From Me');
    }

    public function test_student_sees_except_module_when_they_are_not_excluded(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $studentA = $this->createUser();
        $studentB = $this->createUser();
        $class = $this->createClass($teacher);
        $studentA->classes()->attach($class->id);
        $studentB->classes()->attach($class->id);

        $module = $this->createModule($class, ['title' => 'Except For A Fine For B', 'visibility' => 'except']);
        $module->visibleTo()->attach($studentA->id);

        $this->actingAs($studentB)
            ->get(route('student.modules', $class))
            ->assertOk()
            ->assertSee('Except For A Fine For B');
    }

    // ── createQuizDraft visibility ─────────────────────────────────────

    public function test_quiz_draft_created_with_selected_visibility_syncs_users(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $student = $this->createUser();
        $class = $this->createClass($teacher);
        $student->classes()->attach($class->id);

        $this->actingAs($teacher)
            ->post(route('quiz.create.draft', $class), [
                'title' => 'Vis Quiz Draft',
                'description' => '',
                'time_limit' => 0,
                'is_formal_assessment' => 0,
                'visibility' => 'selected',
                'visible_user_ids' => [$student->id],
            ]);

        $module = Module::query()->where('title', 'Vis Quiz Draft')->first();

        $this->assertNotNull($module);
        $this->assertEquals('selected', $module->visibility);
        $this->assertDatabaseHas('module_user_visibility', [
            'module_id' => $module->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_quiz_draft_excludes_non_enrolled_user_from_visibility(): void
    {
        $teacher = $this->createUser(['role' => 'teacher', 'program' => 'teacher']);
        $enrolled = $this->createUser();
        $outsider = $this->createUser();
        $class = $this->createClass($teacher);
        $enrolled->classes()->attach($class->id);
        // $outsider is NOT enrolled

        $this->actingAs($teacher)
            ->post(route('quiz.create.draft', $class), [
                'title' => 'Vis Quiz Draft Intersect',
                'description' => '',
                'time_limit' => 0,
                'is_formal_assessment' => 0,
                'visibility' => 'selected',
                'visible_user_ids' => [$enrolled->id, $outsider->id],
            ]);

        $module = Module::query()->where('title', 'Vis Quiz Draft Intersect')->first();

        $this->assertNotNull($module);
        $this->assertDatabaseHas('module_user_visibility', ['module_id' => $module->id, 'user_id' => $enrolled->id]);
        $this->assertDatabaseMissing('module_user_visibility', ['module_id' => $module->id, 'user_id' => $outsider->id]);
    }
}
