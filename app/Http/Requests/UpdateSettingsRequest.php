<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'registration_enabled' => 'nullable|boolean',
            'inactivity_timeout_minutes' => 'required|integer|in:5,10,15,30,60',
            'max_execution_time_seconds' => 'required|integer|min:1|max:30',
            'sandbox_memory_limit_mb' => 'required|integer|min:16|max:256',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer',
            'smtp_user' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_from_address' => 'nullable|email',
            'smtp_from_name' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:null,tls,ssl',
        ];
    }
}
