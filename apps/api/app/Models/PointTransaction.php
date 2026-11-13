<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_account_id', 'type', 'points_delta', 'balance_after', 'source_type', 'source_id',
    'idempotency_key', 'reason', 'metadata', 'actor_id',
])]
class PointTransaction extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['metadata' => 'array', 'points_delta' => 'integer', 'balance_after' => 'integer'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class, 'student_account_id');
    }
}
