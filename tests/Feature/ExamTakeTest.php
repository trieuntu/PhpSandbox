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
}
