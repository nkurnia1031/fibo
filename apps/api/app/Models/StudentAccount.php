<?php

namespace App\Models;

use Database\Factories\StudentAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'external_id', 'name', 'email', 'school', 'status', 'attempt_quota', 'attempts_used',
    'access_expires_at', 'closed_at', 'created_by',
])]
class StudentAccount extends Model
{
    /** @use HasFactory<StudentAccountFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'access_expires_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
