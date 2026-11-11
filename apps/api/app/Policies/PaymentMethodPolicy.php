<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Finance'], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, PaymentMethod $paymentMethod): bool
    {
        return $this->viewAny($user) && $paymentMethod->usage_count === 0;
    }
}
