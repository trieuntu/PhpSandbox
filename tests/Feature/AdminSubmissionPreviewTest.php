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
}
