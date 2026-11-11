<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'email' => ['sometimes', 'required', 'email', 'max:190', Rule::unique('student_accounts', 'email')->ignore($this->recordId())],
            'school' => ['sometimes', 'nullable', 'string', 'max:190'],
            'externalId' => ['sometimes', 'nullable', 'string', 'max:160', Rule::unique('student_accounts', 'external_id')->ignore($this->recordId())],
            'status' => ['sometimes', 'in:Aktif,Nonaktif'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function recordId(): int
    {
        return (int) str_replace('M-', '', (string) $this->route('studentAccount'));
    }
}
