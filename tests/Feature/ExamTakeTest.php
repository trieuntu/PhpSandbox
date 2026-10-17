<?php
namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTakeTest extends TestCase {
    use RefreshDatabase;

    private function exam(User $admin, ?string $instructions): Exam {
        $class = Classes::create(['name' => 'C', 'created_by' => $admin->id, 'is_active' => true]);
        return Exam::create([
            'class_id' => $class->id, 'title' => 'E', 'instructions' => $instructions,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'time_limit_minutes' => 30,
            'is_active' => true, 'created_by' => $admin->id,
        ]);
    }

    public function test_admin_can_take_exam_with_rich_and_plain_instructions(): void {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['<h2>Câu 1</h2><pre class="language-php"><code>echo 1;</code></pre>', "Dòng 1\nDòng 2", null] as $text) {
            $exam = $this->exam($admin, $text);
            $this->actingAs($admin)->get(route('student.exams.lobby', $exam))->assertOk();
            $this->actingAs($admin)->post(route('student.exams.start', $exam))
                ->assertRedirect(route('student.exams.editor', $exam));
            $this->actingAs($admin)->get(route('student.exams.editor', $exam))->assertOk();
            $this->actingAs($admin)->get(route('admin.exams.show', $exam))->assertOk();
        }
    }

    public function test_updating_exam_time_limit_syncs_active_attempt_expires_at(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $class = Classes::create(['name' => 'C', 'created_by' => $admin->id, 'is_active' => true]);
        $class->students()->attach($student->id, ['enrolled_at' => now()]);

        $exam = Exam::create([
            'class_id' => $class->id, 'title' => 'Midterm',
            'starts_at' => now()->subMinutes(10), 'ends_at' => now()->addHours(2),
            'time_limit_minutes' => 30, 'is_active' => true, 'created_by' => $admin->id,
        ]);

        // Student starts exam (expires in 30 minutes from now-10)
        $this->actingAs($student)->post(route('student.exams.start', $exam));
        $attempt = $exam->attempts()->where('user_id', $student->id)->firstOrFail();
        $initialExpires = $attempt->expires_at;

        // Admin updates time limit to 60 minutes
        $this->actingAs($admin)->put(route('admin.exams.update', $exam), [
            'class_id' => $class->id,
            'title' => 'Midterm',
            'starts_at' => $exam->starts_at->toDateTimeString(),
            'ends_at' => $exam->ends_at->toDateTimeString(),
            'time_limit_minutes' => 60,
        ])->assertRedirect(route('admin.exams.index'));

        $this->assertTrue($attempt->fresh()->expires_at->gt($initialExpires));
        $this->assertEquals(
            $attempt->started_at->copy()->addMinutes(60)->timestamp,
            $attempt->fresh()->expires_at->timestamp
        );
    }

    public function test_enrolled_student_can_see_lobby_modal_and_start_exam(): void {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'phonexai.x.65cntt@ntu.edu.vn',
            'student_id' => '65139006',
            'first_name' => 'Phonexai',
            'last_name' => 'Xaiyadeth',
        ]);
        $class = Classes::create(['name' => '65.CNTT-CLC', 'created_by' => $admin->id, 'is_active' => true]);
        $class->students()->attach($student->id, ['enrolled_at' => now()]);

        $exam = Exam::create([
            'class_id' => $class->id,
            'title' => 'Bài kiểm tra PHP',
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHours(1),
            'time_limit_minutes' => 45,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($student)->get(route('student.exams.lobby', $exam));
        $response->assertOk();
        $response->assertSee('startExamModal');
        $response->assertSee('Bắt đầu thi');
        $response->assertDontSee('onclick="return confirm(');

        $startResponse = $this->actingAs($student)->post(route('student.exams.start', $exam));
        $startResponse->assertRedirect(route('student.exams.editor', $exam));

        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $exam->id,
            'user_id' => $student->id,
        ]);
    }
}
