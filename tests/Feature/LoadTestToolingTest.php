<?php
namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LoadTestToolingTest extends TestCase {
    use RefreshDatabase;

    public function test_prepare_and_cleanup_commands(): void {
        User::factory()->create(['role' => 'admin']);

        $this->artisan('loadtest:prepare', ['--count' => 5])->assertSuccessful();
        $this->assertSame(5, User::where('email', 'like', 'loadtest%@loadtest.local')->count());
        $this->assertSame(5, Classes::where('name', 'LOADTEST')->first()->students()->count());
        $this->assertTrue(Exam::where('title', 'LOADTEST Exam')->first()->isOpen());

        $this->artisan('loadtest:prepare', ['--count' => 5])->assertSuccessful();
        $this->assertSame(5, User::where('email', 'like', 'loadtest%@loadtest.local')->count());

        $this->artisan('loadtest:cleanup')->assertSuccessful();
        $this->assertSame(0, User::where('email', 'like', 'loadtest%@loadtest.local')->count());
        $this->assertSame(0, Classes::where('name', 'LOADTEST')->count());
    }

    public function test_polling_does_not_consume_the_execute_budget(): void {
        Queue::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student);

        for ($i = 0; $i < 60; $i++) {
            $this->getJson(route('sandbox.poll', 999999))->assertNotFound();
        }

        $this->postJson(route('sandbox.execute'), ['code' => '<?php echo 1;', 'context_type' => 'free'])->assertOk();
        $this->assertNotNull($admin);
    }

    public function test_execute_is_limited_per_minute(): void {
        Queue::fake();
        $this->actingAs(User::factory()->create(['role' => 'student']));

        $last = null;
        for ($i = 0; $i < 21; $i++) {
            $last = $this->postJson(route('sandbox.execute'), ['code' => '<?php echo 1;', 'context_type' => 'free']);
        }
        $last->assertStatus(429);
    }
}
