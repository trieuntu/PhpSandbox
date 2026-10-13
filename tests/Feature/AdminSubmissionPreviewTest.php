<?php
namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubmissionPreviewTest extends TestCase {
    use RefreshDatabase;

    public function test_admin_can_view_submission_detail_with_proper_iframe(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'SV001']);

        $submission = Submission::create([
            'user_id' => $student->id,
            'title' => 'Bài tập Form',
            'code' => '<?php echo "<h1>Form</h1>"; ?>',
            'output_html' => '<h1>Form</h1>',
            'execution_status' => 'success',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.submissions.show', $submission->id));

        $response->assertOk();
        $response->assertSee('sandbox="allow-forms allow-scripts allow-same-origin"', false);
        $response->assertSee(route('sandbox.preview', $submission->id), false);
        $response->assertSee('▶ Chạy lại Code');
        $response->assertSee('Làm mới Preview');
    }

    public function test_admin_can_access_student_submission_preview(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'SV002']);

        $submission = Submission::create([
            'user_id' => $student->id,
            'code' => '<?php echo "<form method=\'post\'><input name=\'val\'><button type=\'submit\'>Submit</button></form>"; ?>',
            'output_html' => '<form method="post"><input name="val"><button type="submit">Submit</button></form>',
            'execution_status' => 'success',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('sandbox.preview', $submission->id));

        $response->assertOk();
        $response->assertSee('var SID=' . $submission->id, false);
        $response->assertSee('/api/sandbox/execute-form/\'+SID', false);
    }

    public function test_admin_can_execute_form_for_student_submission(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'SV003']);

        $code = <<<'PHP'
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? 'World';
    echo "Hello, " . htmlspecialchars($name) . "!";
} else {
    echo '<form method="post"><input name="name"><button type="submit">Send</button></form>';
}
PHP;

        $submission = Submission::create([
            'user_id' => $student->id,
            'code' => $code,
            'output_html' => '<form method="post"><input name="name"><button type="submit">Send</button></form>',
            'execution_status' => 'success',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->postJson(route('sandbox.execute_form', $submission->id), [
            'name' => 'Nguyen Van A',
            '__sb_method__' => 'POST',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('Hello, Nguyen Van A!', $response->getContent());
    }

    public function test_admin_can_rerun_student_submission(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'SV004']);

        $code = '<?php echo "RERUN_OK"; ?>';

        $submission = Submission::create([
            'user_id' => $student->id,
            'code' => $code,
            'output_html' => 'OLD_OUTPUT',
            'execution_status' => 'success',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.submissions.rerun', $submission->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $submission->refresh();
        $this->assertStringContainsString('RERUN_OK', $submission->output_html);
    }

    public function test_non_admin_cannot_access_other_student_preview(): void {
        $student1 = User::factory()->create(['role' => 'student', 'student_id' => 'SV005']);
        $student2 = User::factory()->create(['role' => 'student', 'student_id' => 'SV006']);

        $submission = Submission::create([
            'user_id' => $student1->id,
            'code' => '<?php echo 123; ?>',
            'output_html' => '123',
            'execution_status' => 'success',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student2)->get(route('sandbox.preview', $submission->id));
        $response->assertNotFound();
    }

    public function test_admin_can_view_submitted_attempts_tab(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Tran Thi B', 'student_id' => 'SV007']);

        $class = \App\Models\Classes::create(['name' => 'Lớp 65.CNTT-1', 'created_by' => $admin->id, 'is_active' => true]);
        $exam = \App\Models\Exam::create([
            'title' => 'Thi Giua Ky PHP',
            'class_id' => $class->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $attempt = \App\Models\ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(45),
            'submitted_at' => now()->subMinutes(5),
        ]);

        $submission = Submission::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'code' => '<?php echo "FINAL"; ?>',
            'output_html' => 'FINAL',
            'execution_status' => 'success',
            'submitted_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.submissions.index', ['tab' => 'submitted']));

        $response->assertOk();
        $response->assertSee('Tran Thi B');
        $response->assertSee('SV007');
        $response->assertSee('Thi Giua Ky PHP');
        $response->assertSee('Chấm bài');
        $response->assertSee(route('admin.submissions.show', $submission->id));
    }

    public function test_admin_can_search_submissions_by_student_name(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $student1 = User::factory()->create(['role' => 'student', 'name' => 'Le Van Cuong', 'student_id' => 'SV008']);
        $student2 = User::factory()->create(['role' => 'student', 'name' => 'Pham Thi Dung', 'student_id' => 'SV009']);

        Submission::create([
            'user_id' => $student1->id,
            'code' => '<?php echo "Cuong"; ?>',
            'output_html' => 'Cuong',
            'execution_status' => 'success',
            'submitted_at' => now(),
        ]);

        Submission::create([
            'user_id' => $student2->id,
            'code' => '<?php echo "Dung"; ?>',
            'output_html' => 'Dung',
            'execution_status' => 'success',
            'submitted_at' => now(),
        ]);

        // Search for Cuong on all tab
        $response = $this->actingAs($admin)->get(route('admin.submissions.index', ['tab' => 'all', 'search' => 'Cuong']));
        $response->assertOk();
        $response->assertSee('Le Van Cuong');
        $response->assertDontSee('Pham Thi Dung');

        // Search for SV009 on all tab
        $response2 = $this->actingAs($admin)->get(route('admin.submissions.index', ['tab' => 'all', 'search' => 'SV009']));
        $response2->assertOk();
        $response2->assertSee('Pham Thi Dung');
        $response2->assertDontSee('Le Van Cuong');
    }

    public function test_admin_exam_show_displays_submitted_students(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'Hoang Van E', 'student_id' => 'SV010']);

        $class = \App\Models\Classes::create(['name' => 'Lớp 65.CNTT-2', 'created_by' => $admin->id, 'is_active' => true]);
        $exam = \App\Models\Exam::create([
            'title' => 'Thi Cuoi Ky PHP',
            'class_id' => $class->id,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        \App\Models\ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now()->subMinutes(2),
        ]);

        $sub = Submission::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'code' => '<?php echo "EXAM_FINAL"; ?>',
            'output_html' => 'EXAM_FINAL',
            'execution_status' => 'success',
            'submitted_at' => now()->subMinutes(2),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.exams.show', $exam->id));
        $response->assertOk();
        $response->assertSee('Hoang Van E');
        $response->assertSee('Sinh viên đã nộp bài chính thức');
        $response->assertSee('Chấm bài');
        $response->assertSee(route('admin.submissions.show', $sub->id));
    }
}
