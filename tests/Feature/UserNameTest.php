<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNameTest extends TestCase {
    use RefreshDatabase;

    public function test_admin_creates_user_with_separate_names(): void {
        $this->withoutVite();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'last_name' => 'Nguyễn Văn', 'first_name' => 'An', 'email' => 'an@example.com',
            'password' => 'password123', 'role' => 'student',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'an@example.com')->firstOrFail();
        $this->assertSame('Nguyễn Văn', $user->last_name);
        $this->assertSame('An', $user->first_name);
        $this->assertSame('Nguyễn Văn An', $user->name);
    }

    public function test_both_names_are_required(): void {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'last_name' => '', 'first_name' => '', 'email' => 'x@example.com',
            'password' => 'password123', 'role' => 'student',
        ])->assertSessionHasErrors(['last_name', 'first_name']);
    }

    public function test_update_recomposes_full_name(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'last_name' => 'Trần Thị', 'first_name' => 'Bình', 'email' => $user->email, 'role' => 'student',
        ])->assertRedirect();

        $this->assertSame('Trần Thị Bình', $user->fresh()->name);
    }

    public function test_registration_uses_separate_names(): void {
        $this->post('/register', [
            'student_id' => 'SV9', 'last_name' => 'Lê', 'first_name' => 'Cường', 'email' => 'c@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect();

        $user = User::where('email', 'c@example.com')->firstOrFail();
        $this->assertSame(['Lê', 'Cường'], [$user->last_name, $user->first_name]);
    }

    public function test_legacy_full_name_is_split_on_create(): void {
        $user = User::factory()->create(['name' => 'Phạm Minh Đức']);
        $this->assertSame('Phạm Minh', $user->last_name);
        $this->assertSame('Đức', $user->first_name);

        $this->assertSame(['', 'Admin'], User::splitName(' Admin '));
    }

    public function test_csv_import_supports_new_and_legacy_headers(): void {
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = "last_name,first_name,email,student_id,password\nVõ Thị,Hoa,hoa@example.com,S1,password123\n";
        $legacy = "name,email,student_id,password\nHoàng Văn Nam,nam@example.com,S2,password123\n";

        foreach ([$csv, $legacy] as $i => $content) {
            $file = \Illuminate\Http\UploadedFile::fake()->createWithContent("u{$i}.csv", $content);
            $this->actingAs($admin)->post(route('admin.users.import'), ['csv' => $file])->assertRedirect();
        }

        $this->assertSame('Hoa', User::where('email', 'hoa@example.com')->value('first_name'));
        $this->assertSame('Hoàng Văn', User::where('email', 'nam@example.com')->value('last_name'));
    }
}
