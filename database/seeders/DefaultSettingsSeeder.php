<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class DefaultSettingsSeeder extends Seeder {
    public function run(): void {
        $defaults = [
            'registration_enabled'       => '1',
            'inactivity_timeout_minutes' => '15',
            'max_execution_time_seconds' => '5',
            'sandbox_memory_limit_mb'    => '64',
            'smtp_host'                  => 'mailpit',
            'smtp_port'                  => '1025',
            'smtp_user'                  => '',
            'smtp_password'              => '',
            'smtp_from_address'          => 'noreply@phpsandbox.local',
            'smtp_from_name'             => 'PHP Sandbox',
            'smtp_encryption'            => 'null',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        }
    }
}
