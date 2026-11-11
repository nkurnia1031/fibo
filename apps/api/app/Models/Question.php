<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'question_series_id', 'revision', 'title', 'format', 'education_level', 'subject',
    'framework_version', 'topic', 'subelement', 'cognitive_level', 'prompt', 'options',
    'answer_data', 'explanation', 'misconception', 'tags', 'image_path', 'status',
    'author_id', 'reviewer_id', 'approved_by', 'review_reason', 'submitted_at', 'approved_at',
    'published_at', 'archived_at',
])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'answer_data' => 'array',
            'tags' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(QuestionSeries::class, 'question_series_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function packageItems(): HasMany
    {
        return $this->hasMany(PackageItem::class);
    }
}
