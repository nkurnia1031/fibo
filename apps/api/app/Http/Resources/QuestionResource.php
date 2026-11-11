<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->series?->code,
            'revision' => $this->revision,
            'title' => $this->title,
            'prompt' => $this->prompt,
            'explanation' => $this->explanation,
            'commonMistakes' => $this->misconception,
            'tags' => $this->tags ?? [],
            'format' => $this->format,
            'topic' => $this->subelement,
            'level' => $this->cognitive_level,
            'status' => $this->status,
            'updated' => $this->updated_at?->format('d M Y H:i') ?? 'Baru saja',
            'author' => $this->author?->name,
            'options' => $this->options ?? [],
            'answerKey' => $this->answer_data['answerKey'] ?? null,
            'correctOptions' => $this->answer_data['correctOptions'] ?? [],
            'categoryStatements' => $this->answer_data['categoryStatements'] ?? [],
            'categoryAnswers' => $this->answer_data['categoryAnswers'] ?? [],
            'imageUrl' => $this->image_path ? asset('storage/'.$this->image_path) : '',
            'educationLevel' => $this->education_level,
            'subject' => $this->subject,
            'frameworkVersion' => $this->framework_version,
            'subelement' => $this->subelement,
        ];
    }
}
