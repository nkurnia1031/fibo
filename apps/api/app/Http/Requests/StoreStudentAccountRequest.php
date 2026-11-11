<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentAccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', 'unique:student_accounts,email'],
            'school' => ['nullable', 'string', 'max:190'],
            'externalId' => ['nullable', 'string', 'max:160', 'unique:student_accounts,external_id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
