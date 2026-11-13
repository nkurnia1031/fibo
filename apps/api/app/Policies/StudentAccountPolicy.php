<?php

namespace App\Policies;

use App\Models\StudentAccount;
use App\Models\User;

class StudentAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Support', 'Finance'], true);
    }

    public function create(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Support'], true);
    }

    public function update(User $user, StudentAccount $studentAccount): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Support'], true);
    }

    public function delete(User $user, StudentAccount $studentAccount): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Support'], true)
            && $studentAccount->status === 'Undangan'
            && $studentAccount->attempts_used === 0
            && ! $studentAccount->attempts()->exists()
            && ! $studentAccount->pointTransactions()->exists()
            && ! $studentAccount->topUpRequests()->exists()
            && ! $studentAccount->accesses()->exists();
    }
}
