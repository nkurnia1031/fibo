<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => 'U-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => ! $this->is_active ? 'Nonaktif' : ($this->must_change_password ? 'Undangan' : 'Aktif'),
            'lastActive' => $this->last_login_at?->diffForHumans() ?? 'Belum masuk',
            'protected' => $this->role === 'Owner',
            'mustChangePassword' => $this->must_change_password,
        ];
    }
}
