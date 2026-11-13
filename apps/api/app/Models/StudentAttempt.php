<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_account_id', 'student_access_id', 'test_package_id', 'idempotency_key', 'status',
    'started_at', 'deadline_at', 'submitted_at', 'score_points', 'total_points', 'score_percent',
])]
class StudentAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score_percent' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class, 'student_account_id');
    }

    public function access(): BelongsTo
    {
        return $this->belongsTo(StudentAccess::class, 'student_access_id');
    }

    public function testPackage(): BelongsTo
    {
        return $this->belongsTo(TestPackage::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AttemptResponse::class);
    }
}
