<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutWidthTest extends TestCase {
    use RefreshDatabase;

    public function test_editor_pages_use_the_full_width_container(): void {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'student']));

        $this->get(route('student.sandbox.editor'))
            ->assertOk()
            ->assertSee('workspace-page', false)
            ->assertSee('split-gutter', false)
            ->assertSee('<main class="w-full px-3', false)
            ->assertDontSee('max-w-7xl', false);
    }

    public function test_dashboard_uses_the_wide_container_and_default_pages_keep_7xl(): void {
        $this->withoutVite();
        $this->actingAs(User::factory()->create(['role' => 'student']));

        $this->get(route('student.home'))->assertOk()->assertSee('max-w-screen-2xl', false);
        $this->get(route('student.classes.index'))->assertOk()->assertSee('max-w-7xl', false);
    }
}
