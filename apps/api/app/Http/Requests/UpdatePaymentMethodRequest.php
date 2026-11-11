<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:120', Rule::unique('payment_methods', 'name')->ignore($this->route('code'), 'code')],
            'type' => ['sometimes', 'required', 'string', 'max:80'],
            'provider' => ['sometimes', 'required', 'string', 'max:80'],
            'fee' => ['sometimes', 'nullable', 'string', 'max:120'],
            'isActive' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
