<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGradingAndExportTest extends TestCase
{
    use RefreshDatabase;

    private function createExam(User $admin, ?Classes $class = null): Exam
    {
        $class = $class ?? Classes::create([
            'name' => 'Lớp Test',
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        return Exam::create([
            'class_id' => $class->id,
            'title' => 'Kỳ thi Giữa Kỳ PHP',
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->addHours(2),
            'time_limit_minutes' => 60,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_view_grading_form_on_submission_show(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'SV100']);

        $submission = Submission::create([
            'user_id' => $student->id,
            'title' => 'Bài làm PHP',
            'code' => '<?php echo "Hello"; ?>',
            'output_html' => 'Hello',
            'execution_status' => 'success',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.submissions.show', $submission->id));

        $response->assertOk();
        $response->assertSee('Chấm điểm bài làm');
        $response->assertSee('name="score"', false);
        $response->assertSee('name="teacher_feedback"', false);
        $response->assertSee('Lưu điểm');
    }

    public function test_admin_can_grade_submission_and_sync_to_exam_attempt(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Thầy Giáo A']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'SV101', 'name' => 'Nguyễn Văn B']);

        $exam = $this->createExam($admin);

        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(45),
            'submitted_at' => now(),
        ]);

        $submission = Submission::create([
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'title' => 'Bài thi',
            'code' => '<?php echo "Done"; ?>',
            'output_html' => 'Done',
            'execution_status' => 'success',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.submissions.grade', $submission->id), [
            'score' => 8.5,
            'teacher_feedback' => 'Code chạy chính xác, giải thuật tốt.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $submission->refresh();
        $attempt->refresh();

        $this->assertEquals(8.5, $submission->score);
        $this->assertEquals('Code chạy chính xác, giải thuật tốt.', $submission->teacher_feedback);
        $this->assertEquals($admin->id, $submission->graded_by);
        $this->assertNotNull($submission->graded_at);

        // Verify synced to ExamAttempt
        $this->assertEquals(8.5, $attempt->score);
        $this->assertEquals('Code chạy chính xác, giải thuật tốt.', $attempt->teacher_feedback);
        $this->assertEquals($admin->id, $attempt->graded_by);
        $this->assertNotNull($attempt->graded_at);
    }

    public function test_admin_can_clear_grades(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student', 'student_id' => 'SV102']);

        $submission = Submission::create([
            'user_id' => $student->id,
            'score' => 9.0,
            'teacher_feedback' => 'Tốt',
            'graded_by' => $admin->id,
            'graded_at' => now(),
            'code' => '<?php',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.submissions.grade', $submission->id), [
            'score' => '',
            'teacher_feedback' => '',
        ]);

        $response->assertRedirect();
        $submission->refresh();

        $this->assertNull($submission->score);
        $this->assertNull($submission->teacher_feedback);
        $this->assertNull($submission->graded_by);
        $this->assertNull($submission->graded_at);
    }

    public function test_grading_validation_fails_for_invalid_scores(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $submission = Submission::create([
            'user_id' => $student->id,
            'code' => '<?php',
            'submitted_at' => now(),
        ]);

        // Score > 100
        $response = $this->actingAs($admin)->post(route('admin.submissions.grade', $submission->id), [
            'score' => 105,
        ]);
        $response->assertSessionHasErrors('score');

        // Score < 0
        $response = $this->actingAs($admin)->post(route('admin.submissions.grade', $submission->id), [
            'score' => -1,
        ]);
        $response->assertSessionHasErrors('score');

        // Score non-numeric
        $response = $this->actingAs($admin)->post(route('admin.submissions.grade', $submission->id), [
            'score' => 'not-a-number',
        ]);
        $response->assertSessionHasErrors('score');
    }

    public function test_student_cannot_grade_submissions(): void
    {
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);

        $submission = Submission::create([
            'user_id' => $student1->id,
            'code' => '<?php',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student2)->post(route('admin.submissions.grade', $submission->id), [
            'score' => 10,
        ]);

        $this->assertContains($response->getStatusCode(), [302, 403]);
    }

    public function test_admin_can_export_exam_grades_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Thầy Trưởng Bộ Môn']);
        $class = Classes::create([
            'name' => 'Lớp CNTT K15',
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        $studentA = User::factory()->create([
            'role' => 'student',
            'student_id' => 'B210001',
            'last_name' => 'Trần',
            'first_name' => 'Văn Nam',
            'name' => 'Trần Văn Nam',
            'email' => 'nam.tran@example.com',
        ]);
        $class->students()->attach($studentA->id, ['enrolled_at' => now()]);

        $studentB = User::factory()->create([
            'role' => 'student',
            'student_id' => 'B210002',
            'last_name' => 'Lê',
            'first_name' => 'Thị Hoa',
            'name' => 'Lê Thị Hoa',
            'email' => 'hoa.le@example.com',
        ]);
        $class->students()->attach($studentB->id, ['enrolled_at' => now()]);

        $exam = $this->createExam($admin, $class);

        // Attempt 1: Graded
        ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $studentA->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(60),
            'submitted_at' => now()->subMinutes(5),
            'score' => 9.25,
            'teacher_feedback' => 'Rất tốt, đạt yêu cầu đầy đủ',
            'graded_by' => $admin->id,
            'graded_at' => now(),
        ]);

        // Attempt 2: Ungraded
        ExamAttempt::create([
            'exam_id' => $exam->id,
            'user_id' => $studentB->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(70),
            'submitted_at' => now()->subMinutes(10),
            'score' => null,
            'teacher_feedback' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.exams.export-grades', $exam->id));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('bang_diem_', (string) $response->headers->get('content-disposition'));

        $content = $response->streamedContent();

        // Check UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Check header columns
        $this->assertStringContainsString('STT', $content);
        $this->assertStringContainsString('Mã sinh viên', $content);
        $this->assertStringContainsString('Họ và tên đệm', $content);
        $this->assertStringContainsString('Tên', $content);
        $this->assertStringContainsString('Họ và tên đầy đủ', $content);
        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString('Điểm số', $content);
        $this->assertStringContainsString('Trạng thái chấm', $content);
        $this->assertStringContainsString('Nhận xét của giáo viên', $content);
        $this->assertStringContainsString('Người chấm', $content);

        // Check student rows
        $this->assertStringContainsString('B210001', $content);
        $this->assertStringContainsString('Trần Văn Nam', $content);
        $this->assertStringContainsString('9.25', $content);
        $this->assertStringContainsString('Rất tốt, đạt yêu cầu đầy đủ', $content);
        $this->assertStringContainsString('Thầy Trưởng Bộ Môn', $content);

        $this->assertStringContainsString('B210002', $content);
        $this->assertStringContainsString('Lê Thị Hoa', $content);
        $this->assertStringContainsString('Chưa chấm', $content);
    }

    public function test_student_cannot_export_exam_grades(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $exam = $this->createExam($admin);

        $response = $this->actingAs($student)->get(route('admin.exams.export-grades', $exam->id));
        $this->assertContains($response->getStatusCode(), [302, 403]);
    }
}
