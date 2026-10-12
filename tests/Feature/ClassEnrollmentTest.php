<?php
namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Classes;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassEnrollmentTest extends TestCase {
    use RefreshDatabase;

    private function admin(): User {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function student(array $attrs = []): User {
        return User::factory()->create($attrs + ['role' => 'student', 'is_active' => true]);
    }

    private function classWithExam(User $admin): array {
        $class = Classes::create(['name' => 'PHP 101', 'created_by' => $admin->id, 'is_active' => true]);
        $exam = Exam::create([
            'class_id' => $class->id, 'title' => 'Midterm', 'instructions' => 'Do it',
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(),
            'is_active' => true, 'created_by' => $admin->id,
        ]);
        return [$class, $exam];
    }

    public function test_admin_adds_students_by_id_and_by_identifier_and_removes(): void {
        $this->withoutVite();
        $admin = $this->admin();
        [$class] = $this->classWithExam($admin);
        $a = $this->student(['student_id' => 'SV001']);
        $b = $this->student(['email' => 'b@example.com']);
        $c = $this->student();

        $this->actingAs($admin)->post(route('admin.classes.students.store', $class), ['student_ids' => [$a->id]])
            ->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.classes.students.store', $class), [
            'identifiers' => "SV001, B@Example.com\nUNKNOWN",
        ])->assertSessionHas('warning');

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $class->students()->pluck('users.id')->all());
        $this->assertNotNull($class->enrollments()->first()->enrolled_at);

        $this->actingAs($admin)->delete(route('admin.classes.students.destroy', [$class, $a]));
        $this->assertSame([$b->id], $class->students()->pluck('users.id')->all());
        $this->assertFalse($class->students()->where('users.id', $c->id)->exists());
    }

    public function test_admin_class_page_and_search_work(): void {
        $this->withoutVite();
        $admin = $this->admin();
        [$class] = $this->classWithExam($admin);
        $s = $this->student(['first_name' => 'Zed', 'last_name' => 'Nguyen Van']);

        $this->actingAs($admin)->get(route('admin.classes.show', $class))->assertOk();
        $this->actingAs($admin)->getJson(route('admin.classes.students.search', $class) . '?q=Zed')
            ->assertOk()->assertJsonFragment(['id' => $s->id]);
    }

    public function test_class_form_update_keeps_inactive_enrollments(): void {
        $admin = $this->admin();
        [$class] = $this->classWithExam($admin);
        $inactive = $this->student(['is_active' => false]);
        $active = $this->student();
        $class->students()->attach([$inactive->id => ['enrolled_at' => now()]]);

        $this->actingAs($admin)->put(route('admin.classes.update', $class), [
            'name' => 'PHP 101', 'student_ids' => [$active->id],
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing([$inactive->id, $active->id], $class->students()->pluck('users.id')->all());
    }

    public function test_unenrolled_student_cannot_access_exam(): void {
        $this->withoutVite();
        [, $exam] = $this->classWithExam($this->admin());
        $outsider = $this->student();

        $this->actingAs($outsider)->get(route('student.exams.lobby', $exam))->assertForbidden();
        $this->actingAs($outsider)->post(route('student.exams.start', $exam))->assertForbidden();
        $this->actingAs($outsider)->get(route('student.exams.editor', $exam))->assertForbidden();
        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_enrolled_student_can_open_and_start_exam(): void {
        $this->withoutVite();
        [$class, $exam] = $this->classWithExam($this->admin());
        $student = $this->student();
        $class->students()->attach([$student->id => ['enrolled_at' => now()]]);

        $this->actingAs($student)->get(route('student.exams.lobby', $exam))->assertOk();
        $this->actingAs($student)->post(route('student.exams.start', $exam))
            ->assertRedirect(route('student.exams.editor', $exam));
        $this->assertDatabaseCount('exam_attempts', 1);
    }

    public function test_unenrolled_student_cannot_submit_code_for_exam_or_assignment(): void {
        $admin = $this->admin();
        [$class, $exam] = $this->classWithExam($admin);
        $assignment = Assignment::create([
            'class_id' => $class->id, 'title' => 'HW1', 'is_active' => true, 'created_by' => $admin->id,
        ]);
        $outsider = $this->student();

        $this->actingAs($outsider)->postJson(route('sandbox.execute'), [
            'code' => '<?php echo 1;', 'context_type' => 'exam', 'context_id' => $exam->id,
        ])->assertForbidden();
        $this->actingAs($outsider)->postJson(route('sandbox.execute'), [
            'code' => '<?php echo 1;', 'context_type' => 'assignment', 'context_id' => $assignment->id,
        ])->assertForbidden();
    }

    public function test_assignment_must_belong_to_class_in_url(): void {
        $this->withoutVite();
        $admin = $this->admin();
        [$classA] = $this->classWithExam($admin);
        $classB = Classes::create(['name' => 'B', 'created_by' => $admin->id, 'is_active' => true]);
        $assignmentB = Assignment::create([
            'class_id' => $classB->id, 'title' => 'HW', 'is_active' => true, 'created_by' => $admin->id,
        ]);
        $student = $this->student();
        $classA->students()->attach([$student->id => ['enrolled_at' => now()]]);

        $this->actingAs($student)->get(route('student.classes.assignment', [$classA, $assignmentB]))->assertNotFound();
        $this->actingAs($student)->get(route('student.classes.show', $classB))->assertForbidden();
    }
}
