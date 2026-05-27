<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class CreateExamRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'class_id' => 'required|exists:classes,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'time_limit_minutes' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
