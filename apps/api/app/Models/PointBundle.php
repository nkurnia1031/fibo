<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'transfer_amount', 'points', 'bonus_points', 'is_active', 'created_by'])]
class PointBundle extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function topUpRequests(): HasMany
    {
        return $this->hasMany(TopUpRequest::class);
    }
}
