<?php

namespace App\Models;

use App\Notifications\StudentPasswordReset;
use App\Notifications\StudentVerifyEmail;
use Database\Factories\StudentAccountFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;

#[Fillable([
    'external_id', 'name', 'email', 'password', 'school', 'status', 'attempt_quota', 'attempts_used',
    'access_expires_at', 'closed_at', 'anonymized_at', 'created_by', 'email_verified_at', 'point_balance',
])]
class StudentAccount extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<StudentAccountFactory> */
    use HasFactory, Notifiable;

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'access_expires_at' => 'datetime',
            'closed_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'point_balance' => 'integer',
        ];
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(StudentAccess::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(StudentAttempt::class);
    }

    public function topUpRequests(): HasMany
    {
        return $this->hasMany(TopUpRequest::class);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill(['email_verified_at' => now()])->save();
    }

    public function markEmailAsUnverified(): bool
    {
        return $this->forceFill(['email_verified_at' => null])->save();
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    public function sendEmailVerificationNotification(): void
    {
        Notification::send($this, new StudentVerifyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        Notification::send($this, new StudentPasswordReset($token));
    }
}
