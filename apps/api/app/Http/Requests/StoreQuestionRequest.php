<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreQuestionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string', 'max:80'],
            'educationLevel' => ['sometimes', 'string', 'max:40'],
            'subject' => ['sometimes', 'string', 'max:80'],
            'frameworkVersion' => ['sometimes', 'string', 'max:40'],
            'topic' => ['required', 'string', 'max:120'],
            'subelement' => ['sometimes', 'string', 'max:160'],
            'level' => ['required', 'string', 'max:80'],
            'prompt' => ['required', 'string', 'max:30000'],
            'options' => ['nullable', 'array', 'max:20'],
            'options.*' => ['string', 'max:4000'],
            'answerKey' => ['nullable', 'integer', 'min:0'],
            'correctOptions' => ['nullable', 'array', 'max:20'],
            'correctOptions.*' => ['integer', 'min:0'],
            'categoryStatements' => ['nullable', 'array', 'max:20'],
            'categoryStatements.*' => ['string', 'max:4000'],
            'categoryAnswers' => ['nullable', 'array', 'max:20'],
            'categoryAnswers.*' => ['in:Benar,Salah'],
            'explanation' => ['required', 'string', 'max:30000'],
            'commonMistakes' => ['nullable', 'string', 'max:4000'],
            'tags' => ['nullable', 'array', 'max:30'],
            'tags.*' => ['string', 'max:80'],
            'status' => ['sometimes', 'in:Draf,Pemeriksaan'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'removeImage' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $format = $this->string('format')->toString();
            $optionCount = count($this->input('options', []));
            if ($format === 'Pilihan ganda') {
                $answerKey = $this->input('answerKey');
                if ($optionCount < 2 || ! is_numeric($answerKey) || (int) $answerKey >= $optionCount) {
                    $validator->errors()->add('answerKey', 'Pilihan ganda perlu sedikitnya dua pilihan dan satu kunci yang valid.');
                }
            } elseif ($format === 'PG kompleks · MCMA') {
                $answers = $this->input('correctOptions', []);
                if ($optionCount < 2 || ! is_array($answers) || count($answers) === 0 || collect($answers)->contains(fn ($answer): bool => (int) $answer >= $optionCount)) {
                    $validator->errors()->add('correctOptions', 'Pilih sedikitnya satu kunci yang sesuai dengan pilihan yang tersedia.');
                }
            } elseif ($format === 'PG kompleks · Kategori') {
                $statements = $this->input('categoryStatements', []);
                $answers = $this->input('categoryAnswers', []);
                if (! is_array($statements) || count($statements) === 0 || ! is_array($answers) || count($answers) !== count($statements)) {
                    $validator->errors()->add('categoryAnswers', 'Setiap pernyataan kategori perlu tepat satu jawaban Benar atau Salah.');
                }
            }
        }];
    }
}
