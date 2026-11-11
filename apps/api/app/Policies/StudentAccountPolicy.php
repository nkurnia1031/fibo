<?php

namespace App\Policies;

use App\Models\StudentAccount;
use App\Models\User;

class StudentAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Support'], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, StudentAccount $studentAccount): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, StudentAccount $studentAccount): bool
    {
        return $this->viewAny($user)
            && $studentAccount->status === 'Undangan'
            && $studentAccount->attempts_used === 0;
    }
}
