<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_attempt_id', 'question_id', 'response', 'is_correct', 'score_points', 'answered_at'])]
class AttemptResponse extends Model
{
    protected function casts(): array
    {
        return ['response' => 'array', 'is_correct' => 'boolean', 'answered_at' => 'datetime'];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(StudentAttempt::class, 'student_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
