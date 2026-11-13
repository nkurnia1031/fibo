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
            default => $this->hasActiveAccess()
                ? ($this->access_expires_at?->lessThanOrEqualTo(now()->addDays(3)) ? 'Segera habis' : 'Aktif')
                : ($this->access_expires_at?->isPast() ? 'Kedaluwarsa' : 'Belum ada'),
        };

        return [
            'id' => 'M-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'email' => $this->email,
            'emailVerified' => $this->hasVerifiedEmail(),
            'pointBalance' => (int) ($this->point_balance ?? 0),
            'school' => $this->school ?? 'Belum ditentukan',
            'externalId' => $this->external_id,
            'status' => $this->status,
            'access' => $accessStatus,
            'expires' => $this->access_expires_at?->format('d M Y') ?? '—',
            'attempts' => $this->attempt_quota > 0 ? $this->attempts_used.' / '.$this->attempt_quota : $this->attempts_used.' percobaan',
        ];
    }

    private function hasActiveAccess(): bool
    {
        $activeAccessCount = $this->getAttribute('active_accesses_count');
        if ($activeAccessCount === null) {
            $activeAccessCount = $this->accesses()
                ->where('status', 'Aktif')
                ->where(fn ($access) => $access->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->where(fn ($access) => $access->whereNull('student_accesses.attempt_quota')->orWhereColumn('student_accesses.attempts_used', '<', 'student_accesses.attempt_quota'))
                ->exists() ? 1 : 0;
        }

        if ($activeAccessCount > 0) {
            return true;
        }

        return $this->attempt_quota > 0
            && $this->attempts_used < $this->attempt_quota
            && ! $this->access_expires_at?->isPast();
    }
}
