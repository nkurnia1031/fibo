<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'student_account_id', 'point_bundle_id', 'payment_method_id', 'bundle_name', 'transfer_amount',
    'points', 'bonus_points', 'payment_method_name', 'payment_reference', 'proof_path', 'status',
    'payment_details', 'idempotency_key', 'reviewed_by', 'review_reason', 'reviewed_at',
])]
class TopUpRequest extends Model
{
    protected function casts(): array
    {
        return ['transfer_amount' => 'integer', 'points' => 'integer', 'bonus_points' => 'integer', 'payment_details' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class, 'student_account_id');
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(PointBundle::class, 'point_bundle_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
