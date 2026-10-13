<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_headerless_ntu_csv_format(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $csvContent = "Huỳnh Triều,An,an.ht.65cntt@ntu.edu.vn,65130011,65130011\n"
                    . "Lê Quốc,Anh,anh.lq.65cntt@ntu.edu.vn,65130081,65130081\n";

        $file = UploadedFile::fake()->createWithContent('ntu.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.import'), ['csv' => $file]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user1 = User::where('email', 'an.ht.65cntt@ntu.edu.vn')->firstOrFail();
        $this->assertSame('Huỳnh Triều', $user1->last_name);
        $this->assertSame('An', $user1->first_name);
        $this->assertSame('65130011', $user1->student_id);
        $this->assertTrue($user1->is_active);

        $user2 = User::where('email', 'anh.lq.65cntt@ntu.edu.vn')->firstOrFail();
        $this->assertSame('Lê Quốc', $user2->last_name);
        $this->assertSame('Anh', $user2->first_name);
        $this->assertSame('65130081', $user2->student_id);
    }

    public function test_imports_csv_with_utf8_bom(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $csvContent = "\xEF\xBB\xBFHuỳnh Triều,An,an.ht.65cntt@ntu.edu.vn,65130011,65130011\n";

        $file = UploadedFile::fake()->createWithContent('bom.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.import'), ['csv' => $file]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'an.ht.65cntt@ntu.edu.vn')->firstOrFail();
        $this->assertSame('Huỳnh Triều', $user->last_name);
        $this->assertSame('An', $user->first_name);
    }

    public function test_imports_csv_with_vietnamese_headers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $csvContent = "Họ và tên đệm,Tên,Email,Mã số sinh viên,Mật khẩu\n"
                    . "Nguyễn Ngọc,Bình,binh.nn.65cntt@ntu.edu.vn,65130230,password123\n";

        $file = UploadedFile::fake()->createWithContent('vn_headers.csv', $csvContent);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.import'), ['csv' => $file]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'binh.nn.65cntt@ntu.edu.vn')->firstOrFail();
        $this->assertSame('Nguyễn Ngọc', $user->last_name);
        $this->assertSame('Bình', $user->first_name);
        $this->assertSame('65130230', $user->student_id);
    }

    public function test_supports_csv_file_modal_input_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        $csvContent = "name,email,student_id,password\n"
                    . "Trần Văn A,a.tv@example.com,SV01,password123\n";

        $file = UploadedFile::fake()->createWithContent('modal.csv', $csvContent);

        // Uses csv_file as key (from index modal)
        $response = $this->actingAs($admin)
            ->post(route('admin.users.import'), ['csv_file' => $file]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'a.tv@example.com')->firstOrFail();
        $this->assertSame('Trần Văn A', $user->name);
    }

    public function test_artisan_command_imports_csv(): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tmpPath, "Huỳnh Triều,An,cmd.an@ntu.edu.vn,65130011,pass123\n");

        $this->artisan('students:import', ['csv_path' => $tmpPath])
            ->expectsOutputToContain('Import complete: 1 imported, 0 skipped.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'cmd.an@ntu.edu.vn',
            'first_name' => 'An',
            'last_name' => 'Huỳnh Triều',
        ]);

        @unlink($tmpPath);
    }
}
