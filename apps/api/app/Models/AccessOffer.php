<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code', 'product_type', 'test_package_id', 'material_code', 'title', 'description',
    'price_points', 'validity_days', 'attempt_quota', 'is_active', 'created_by',
])]
class AccessOffer extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'price_points' => 'integer'];
    }

    public function testPackage(): BelongsTo
    {
        return $this->belongsTo(TestPackage::class);
    }

    public function studentAccesses(): HasMany
    {
        return $this->hasMany(StudentAccess::class);
    }
}
