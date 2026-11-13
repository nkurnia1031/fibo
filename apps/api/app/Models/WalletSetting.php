<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'signup_bonus_points'])]
class WalletSetting extends Model
{
    protected function casts(): array
    {
        return ['signup_bonus_points' => 'integer'];
    }
}
