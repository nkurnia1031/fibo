<?php

namespace App\Policies;

use App\Models\User;

class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->role === 'Owner';
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $staff): bool
    {
        return $this->viewAny($user) && $staff->id !== $user->id;
    }

    public function delete(User $user, User $staff): bool
    {
        return $this->update($user, $staff) && $staff->must_change_password;
    }
}
