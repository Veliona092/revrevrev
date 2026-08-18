<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ClassModel;
use App\Models\Module;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ────────────────────────────────────────────────────────

    private static int $userSeq = 1000;

    private static int $classSeq = 100;

    private static int $moduleSeq = 100;

    private function createStudent(array $overrides = []): User
    {
        $n = ++self::$userSeq;

        return User::query()->create(array_merge([
            'idnumber' => 'STU'.$n,
            'name' => 'Student '.$n,
            'email' => 'student'.$n.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'program' => 'educ',
            'status' => 'active',
            'program_locked' => false,
            'email_verified_at' => now(),
        ], $overrides));
    }

    private function createTeacher(): User
    {
        $n = ++self::$userSeq;

        return User::query()->create([
            'idnumber' => 'TCH'.$n,
            'name' => 'Teacher '.$n,
            'email' => 'teacher'.$n.'@example.test',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'status' => 'active',
            'program_locked' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function createClass(User $teacher): ClassModel
    {
        $n = ++self::$classSeq;

        return ClassModel::query()->create([
            'name' => 'Class '.$n,
            'code' => 'C'.$n,
            'created_by' => $teacher->id,
        ]);
    }

    private function createQuizModule(ClassModel $class): Module
    {
        $n = ++self::$moduleSeq;

        return Module::query()->create([
            'class_id' => $class->id,
            'title' => 'Quiz '.$n,
            'is_quiz' => true,
            'is_formal_assessment' => true,
            'order' => $n,
        ]);
    }

    // ── Auth guard ─────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_from_educ_dashboard(): void
    {
        $this->get(route('educDashboard'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_is_redirected_from_psych_dashboard(): void
    {
        $this->get(route('psychDashboard'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_is_redirected_from_accountancy_dashboard(): void
    {
        $this->get(route('accountancyDashboard'))->assertRedirect(route('login'));
    }

    // ── View dispatch by program ───────────────────────────────────────

    public function test_educ_student_sees_educ_dashboard(): void
    {
        $student = $this->createStudent(['program' => 'educ']);

        $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk()
            ->assertViewIs('pages.educ.educ');
    }

    public function test_psych_student_sees_psych_dashboard(): void
    {
        $student = $this->createStudent(['program' => 'psych']);

        $this->actingAs($student)
            ->get(route('psychDashboard'))
            ->assertOk()
            ->assertViewIs('pages.psych.psych');
    }

    public function test_accountancy_student_sees_accountancy_dashboard(): void
    {
        $student = $this->createStudent(['program' => 'accountancy']);

        $this->actingAs($student)
            ->get(route('accountancyDashboard'))
            ->assertOk()
            ->assertViewIs('pages.accountancy.accountancy');
    }

    // ── Stat: Enrolled classes ─────────────────────────────────────────

    public function test_enrolled_classes_count_matches_class_user_rows(): void
    {
        $student = $this->createStudent(['program' => 'educ']);
        $teacher = $this->createTeacher();

        $class1 = $this->createClass($teacher);
        $class2 = $this->createClass($teacher);
        $student->classes()->attach([$class1->id, $class2->id]);

        $response = $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk();

        $this->assertSame(2, $response->viewData('enrolledClasses'));
    }

    // ── Stat: Pending assignments ──────────────────────────────────────

    public function test_pending_assignments_excludes_already_attempted_modules(): void
    {
        $student = $this->createStudent(['program' => 'educ']);
        $teacher = $this->createTeacher();
        $class = $this->createClass($teacher);
        $student->classes()->attach($class->id);

        $attemptedModule = $this->createQuizModule($class);
        $this->createQuizModule($class); // pending — no attempt

        QuizAttempt::query()->create([
            'user_id' => $student->id,
            'module_id' => $attemptedModule->id,
            'score' => 8,
            'total' => 10,
            'percentage' => 80,
            'passed' => true,
        ]);

        $response = $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk();

        $this->assertSame(1, $response->viewData('pendingAssignments'));
    }

    public function test_pending_assignments_for_non_enrolled_classes_not_counted(): void
    {
        $student = $this->createStudent(['program' => 'educ']);
        $teacher = $this->createTeacher();

        $enrolledClass = $this->createClass($teacher);
        $otherClass = $this->createClass($teacher);

        $student->classes()->attach($enrolledClass->id);

        $this->createQuizModule($otherClass); // belongs to a class student isn't enrolled in

        $response = $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk();

        $this->assertSame(0, $response->viewData('pendingAssignments'));
    }

    // ── Stat: Overall avg ─────────────────────────────────────────────

    public function test_overall_avg_is_average_of_students_quiz_attempts(): void
    {
        $student = $this->createStudent(['program' => 'educ']);
        $teacher = $this->createTeacher();
        $class = $this->createClass($teacher);
        $student->classes()->attach($class->id);

        $module1 = $this->createQuizModule($class);
        $module2 = $this->createQuizModule($class);

        QuizAttempt::query()->create([
            'user_id' => $student->id,
            'module_id' => $module1->id,
            'score' => 6,
            'total' => 10,
            'percentage' => 60,
            'passed' => false,
        ]);

        QuizAttempt::query()->create([
            'user_id' => $student->id,
            'module_id' => $module2->id,
            'score' => 8,
            'total' => 10,
            'percentage' => 80,
            'passed' => true,
        ]);

        $response = $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk();

        $this->assertSame(70, $response->viewData('overallAvg'));
    }

    public function test_overall_avg_is_zero_when_no_attempts_exist(): void
    {
        $student = $this->createStudent(['program' => 'educ']);

        $response = $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk();

        $this->assertSame(0, $response->viewData('overallAvg'));
    }

    // ── Announcements scoped to enrolled classes ───────────────────────

    public function test_announcements_are_scoped_to_enrolled_classes(): void
    {
        $student = $this->createStudent(['program' => 'educ']);
        $teacher = $this->createTeacher();

        $enrolledClass = $this->createClass($teacher);
        $otherClass = $this->createClass($teacher);

        $student->classes()->attach($enrolledClass->id);

        Announcement::query()->create([
            'class_id' => $enrolledClass->id,
            'user_id' => $teacher->id,
            'message' => 'Enrolled class announcement',
        ]);

        Announcement::query()->create([
            'class_id' => $otherClass->id,
            'user_id' => $teacher->id,
            'message' => 'Other class announcement',
        ]);

        $response = $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk();

        $announcements = $response->viewData('announcements');

        $this->assertCount(1, $announcements);
        $this->assertSame('Enrolled class announcement', $announcements->first()->message);
    }

    public function test_announcements_limited_to_three(): void
    {
        $student = $this->createStudent(['program' => 'educ']);
        $teacher = $this->createTeacher();
        $class = $this->createClass($teacher);
        $student->classes()->attach($class->id);

        for ($i = 1; $i <= 5; $i++) {
            Announcement::query()->create([
                'class_id' => $class->id,
                'user_id' => $teacher->id,
                'message' => 'Announcement '.$i,
            ]);
        }

        $response = $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk();

        $this->assertCount(3, $response->viewData('announcements'));
    }

    // ── Messages scoped to participant chats ───────────────────────────

    public function test_messages_only_shows_chats_student_participates_in(): void
    {
        $student = $this->createStudent(['program' => 'educ']);
        $teacher = $this->createTeacher();
        $otherUser = $this->createTeacher();

        // Chat the student is part of
        $chat = Chat::query()->create(['kind' => 'direct', 'created_by' => $teacher->id]);
        $chat->participants()->attach([$student->id, $teacher->id]);
        ChatMessage::query()->create([
            'chat_id' => $chat->id,
            'sender_id' => $teacher->id,
            'body' => 'Hello student',
        ]);

        // Chat the student is NOT part of
        $otherChat = Chat::query()->create(['kind' => 'direct', 'created_by' => $otherUser->id]);
        $otherChat->participants()->attach([$otherUser->id]);

        $response = $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk();

        $messages = $response->viewData('recentMessages');

        $this->assertCount(1, $messages);
        $this->assertSame($teacher->id, $messages->first()['other']->id);
        $this->assertSame('Hello student', $messages->first()['preview']);
    }

    public function test_messages_limited_to_two_conversations(): void
    {
        $student = $this->createStudent(['program' => 'educ']);
        $teacher1 = $this->createTeacher();
        $teacher2 = $this->createTeacher();
        $teacher3 = $this->createTeacher();

        foreach ([$teacher1, $teacher2, $teacher3] as $t) {
            $chat = Chat::query()->create(['kind' => 'direct', 'created_by' => $t->id]);
            $chat->participants()->attach([$student->id, $t->id]);
            ChatMessage::query()->create([
                'chat_id' => $chat->id,
                'sender_id' => $t->id,
                'body' => 'Message from teacher',
            ]);
        }

        $response = $this->actingAs($student)
            ->get(route('educDashboard'))
            ->assertOk();

        $this->assertCount(2, $response->viewData('recentMessages'));
    }
}
