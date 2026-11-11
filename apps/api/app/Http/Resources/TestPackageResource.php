<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestPackageResource extends JsonResource
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
            'version' => $this->version,
            'name' => $this->name,
            'questionItems' => $this->items->map(fn ($item): array => [
                'questionId' => $item->question?->series?->code,
                'revision' => $item->question?->revision,
            ])->values(),
            'questions' => $this->items->count(),
            'duration' => $this->duration_minutes ?? 0,
            'attempts' => $this->attempts,
            'status' => $this->status,
            'updated' => $this->updated_at?->format('d M Y H:i') ?? 'Baru saja',
            'framework' => $this->education_level.' · '.$this->subject.' · '.$this->framework_version,
            'educationLevel' => $this->education_level,
            'subject' => $this->subject,
            'frameworkVersion' => $this->framework_version,
        ];
    }
}
