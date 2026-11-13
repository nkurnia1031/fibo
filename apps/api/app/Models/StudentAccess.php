<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_account_id', 'access_offer_id', 'test_package_id', 'material_code', 'offer_title',
    'price_points', 'attempt_quota', 'attempts_used', 'starts_at', 'expires_at', 'status', 'idempotency_key',
])]
class StudentAccess extends Model
{
    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime', 'price_points' => 'integer'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class, 'student_account_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(AccessOffer::class, 'access_offer_id');
    }

    public function testPackage(): BelongsTo
    {
        return $this->belongsTo(TestPackage::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(StudentAttempt::class);
    }
}
