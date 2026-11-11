<?php

namespace App\Models;

use Database\Factories\QuestionSeriesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'created_by'])]
class QuestionSeries extends Model
{
    /** @use HasFactory<QuestionSeriesFactory> */
    use HasFactory;

    public function revisions(): HasMany
    {
        return $this->hasMany(Question::class)->orderByDesc('revision');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
