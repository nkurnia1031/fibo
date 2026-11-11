<?php

namespace App\Models;

use Database\Factories\TestPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'package_series_id', 'version', 'name', 'education_level', 'subject', 'framework_version',
    'duration_minutes', 'attempts', 'status', 'author_id', 'reviewer_id', 'approved_by',
    'review_reason', 'submitted_at', 'approved_at', 'published_at', 'archived_at',
])]
class TestPackage extends Model
{
    /** @use HasFactory<TestPackageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(PackageSeries::class, 'package_series_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class)->orderBy('position');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
