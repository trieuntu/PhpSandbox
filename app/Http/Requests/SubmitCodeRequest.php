<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class SubmitCodeRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'code'         => 'nullable|string|max:100000',
            'files'        => 'nullable|array|max:20',
            'files.*'      => 'string|max:100000',
            'context_type' => 'required|in:assignment,exam,free',
            'context_id'   => 'nullable|integer',
            'title'        => 'nullable|string|max:255',
        ];
    }
}
