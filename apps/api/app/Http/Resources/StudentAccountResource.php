<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $accessStatus = match ($this->status) {
            'Undangan' => 'Undangan',
            'Nonaktif' => 'Nonaktif',
            'Ditutup' => 'Ditutup',
            default => $this->access_expires_at?->isPast()
                ? 'Kedaluwarsa'
                : ($this->access_expires_at?->lessThanOrEqualTo(now()->addDays(3)) ? 'Segera habis' : 'Aktif'),
        };

        return [
            'id' => 'M-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'email' => $this->email,
            'school' => $this->school ?? 'Belum ditentukan',
            'externalId' => $this->external_id,
            'status' => $this->status,
            'access' => $accessStatus,
            'expires' => $this->access_expires_at?->format('d M Y') ?? '—',
            'attempts' => $this->attempts_used.' / '.$this->attempt_quota,
        ];
    }
}
