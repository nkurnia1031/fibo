<?php

namespace App\Models;

use Database\Factories\PackageItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['test_package_id', 'question_id', 'position'])]
class PackageItem extends Model
{
    /** @use HasFactory<PackageItemFactory> */
    use HasFactory;

    public function testPackage(): BelongsTo
    {
        return $this->belongsTo(TestPackage::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
