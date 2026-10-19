<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\SandboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SandboxErrorLineReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_line_number_matches_student_code_in_single_file_mode(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $service = app(SandboxService::class);

        // Code has error on line 2
        $code = "<?php\ncall_to_undefined_function_xyz();\n";

        $result = $service->executeWithPost($user, $code, [], 'free', null);

        $this->assertEquals('error', $result['status']);
        $this->assertNotEmpty($result['errors']);
        
        // Must report line 2, NOT line 30+
        $this->assertMatchesRegularExpression('/index\.php(?:\(\d+\))?[: ]+(?:on )?line 2/i', $result['errors']);
        
        // Must NOT contain internal wrapper filenames or temp directory paths
        $this->assertStringNotContainsString('__sb_run__', $result['errors']);
        $this->assertStringNotContainsString('__sb_boot__', $result['errors']);
        $this->assertStringNotContainsString('/tmp/phpsandbox_', $result['errors']);
    }

    public function test_error_line_number_matches_student_code_in_multi_file_mode(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $service = app(SandboxService::class);

        $files = [
            'index.php' => "<?php\nrequire 'calc.php';\n",
            'calc.php'  => "<?php\n// line 2\ninvalid_function_abc();\n",
        ];

        $result = $service->executeWithPost($user, '', [], 'free', null, $files, 'index.php');

        $this->assertEquals('error', $result['status']);
        $this->assertNotEmpty($result['errors']);

        // Error is in calc.php on line 3
        $this->assertMatchesRegularExpression('/calc\.php(?:\(\d+\))?[: ]+(?:on )?line 3/i', $result['errors']);

        // Must NOT contain internal wrapper filenames or temp directory paths
        $this->assertStringNotContainsString('__sb_run__', $result['errors']);
        $this->assertStringNotContainsString('__sb_boot__', $result['errors']);
        $this->assertStringNotContainsString('/tmp/phpsandbox_', $result['errors']);
    }

    public function test_syntax_parse_error_reports_exact_line_number(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $service = app(SandboxService::class);

        // Syntax error on line 2
        $code = "<?php\nthis is a syntax error;\n";

        $result = $service->executeWithPost($user, $code, [], 'free', null);

        $this->assertEquals('error', $result['status']);
        $this->assertNotEmpty($result['errors']);

        // Must report line 2
        $this->assertMatchesRegularExpression('/index\.php(?:\(\d+\))?[: ]+(?:on )?line 2/i', $result['errors']);
        $this->assertStringNotContainsString('__sb_run__', $result['errors']);
        $this->assertStringNotContainsString('__sb_boot__', $result['errors']);
    }

    public function test_successful_execution_still_works_properly(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $service = app(SandboxService::class);

        $code = "<?php echo 'HELLO_SANDBOX_SUCCESS'; ?>";

        $result = $service->executeWithPost($user, $code, [], 'free', null);

        $this->assertEquals('success', $result['status']);
        $this->assertStringContainsString('HELLO_SANDBOX_SUCCESS', $result['output']);
        $this->assertEmpty($result['errors']);
    }

    public function test_infinite_loop_triggers_timeout_gracefully(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $service = app(SandboxService::class);

        // Configure a short timeout of 1 second for this test
        config(['sandbox.max_execution_time' => 1]);

        $code = "<?php while(true) {}";

        $result = $service->executeWithPost($user, $code, [], 'free', null);

        $this->assertContains($result['status'], ['timeout', 'error']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_large_output_does_not_deadlock(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $service = app(SandboxService::class);

        // Generate 100k bytes of stderr and stdout
        $code = "<?php fwrite(STDERR, str_repeat('E', 100000)); echo str_repeat('O', 10000);";

        $result = $service->executeWithPost($user, $code, [], 'free', null);

        $this->assertContains($result['status'], ['success', 'error']);
        $this->assertNotEmpty($result['output']);
    }
}
