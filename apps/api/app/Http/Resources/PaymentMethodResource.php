<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'provider' => $this->provider,
            'fee' => $this->fee_info,
            'usage' => $this->usage_count,
            'status' => $this->is_active ? 'Aktif' : 'Nonaktif',
            'isActive' => $this->is_active,
        ];
    }
}
