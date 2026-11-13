<?php

namespace App\Models;

use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code', 'name', 'type', 'provider', 'fee_info', 'account_name', 'account_number', 'instructions',
    'usage_count', 'is_active', 'created_by',
])]
class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
