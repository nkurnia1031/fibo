<?php

namespace App\Models;

use Database\Factories\PackageSeriesFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'created_by'])]
class PackageSeries extends Model
{
    /** @use HasFactory<PackageSeriesFactory> */
    use HasFactory;

    public function versions(): HasMany
    {
        return $this->hasMany(TestPackage::class)->orderByDesc('version');
    }
}
