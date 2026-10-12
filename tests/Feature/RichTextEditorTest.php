<?php
namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RichTextEditorTest extends TestCase {
    use RefreshDatabase;

    public function test_sanitizer_strips_scripts_but_keeps_formatting(): void {
        $html = '<p onclick="x()">Hi <strong>there</strong></p><script>alert(1)</script>'
            . '<img src="/media/abc" onerror="x()"><a href="javascript:alert(1)">l</a>'
            . '<pre class="language-php"><code>echo 1;</code></pre>';
        $clean = HtmlSanitizer::clean($html);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('<strong>there</strong>', $clean);
        $this->assertStringContainsString('src="/media/abc"', $clean);
        $this->assertStringContainsString('language-php', $clean);
        $this->assertNull(HtmlSanitizer::clean('  '));
    }

    public function test_render_keeps_legacy_plain_text_line_breaks(): void {
        $this->assertSame("a<br />\n&lt;b&gt;", HtmlSanitizer::render("a\n<b>"));
        $this->assertSame('<p>x</p>', HtmlSanitizer::render('<p>x</p>'));
    }

    public function test_admin_saves_sanitized_exam_instructions(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $class = Classes::create(['name' => 'C', 'created_by' => $admin->id, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.exams.store'), [
            'title' => 'E', 'class_id' => $class->id,
            'starts_at' => now()->format('Y-m-d H:i:s'), 'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'instructions' => '<h2>Câu 1</h2><script>alert(1)</script><pre class="language-php"><code>echo 1;</code></pre>',
        ])->assertRedirect(route('admin.exams.index'));

        $exam = Exam::firstOrFail();
        $this->assertStringContainsString('<h2>Câu 1</h2>', $exam->instructions);
        $this->assertStringNotContainsString('<script', $exam->instructions);
    }

    public function test_exam_form_renders_editor(): void {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.exams.create'))
            ->assertOk()->assertSee('rich-editor', false)->assertSee('tinymce.min.js', false);
    }

    public function test_admin_uploads_image_and_authenticated_user_can_view_it(): void {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $res = $this->actingAs($admin)->postJson(route('admin.uploads.image'), [
            'file' => UploadedFile::fake()->image('q.png', 50, 50),
        ])->assertOk();
        $location = $res->json('location');
        $this->assertStringStartsWith('/media/', $location);

        $this->actingAs($student)->get($location)->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_media_requires_login(): void {
        $this->get('/media/00000000-0000-0000-0000-000000000000')->assertRedirect('/login');
    }

    public function test_upload_rejects_non_images_and_students(): void {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->postJson(route('admin.uploads.image'), [
            'file' => UploadedFile::fake()->create('x.php', 1, 'text/x-php'),
        ])->assertStatus(422);
        $this->actingAs($student)->postJson(route('admin.uploads.image'), [
            'file' => UploadedFile::fake()->image('q.png'),
        ])->assertForbidden();
    }
}
