<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'student_id' => 'nullable|string|max:50|unique:users,student_id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,student',
        ];
    }
}
