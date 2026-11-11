<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTestPackageRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:360'],
            'educationLevel' => ['sometimes', 'string', 'max:40'],
            'subject' => ['sometimes', 'string', 'max:80'],
            'frameworkVersion' => ['sometimes', 'string', 'max:40'],
            'questionItems' => ['required', 'array', 'min:1', 'max:200'],
            'questionItems.*.questionId' => ['required', 'string', 'max:20'],
            'questionItems.*.revision' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:Draf,Pemeriksaan'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
