<?php

namespace App\Policies;

use App\Models\TestPackage;
use App\Models\User;

class TestPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Penulis Konten', 'Reviewer'], true);
    }

    public function create(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Penulis Konten'], true);
    }

    public function update(User $user, TestPackage $testPackage): bool
    {
        return $testPackage->status === 'Draf'
            && ($testPackage->author_id === $user->id || in_array($user->role, ['Owner', 'Admin'], true));
    }

    public function delete(User $user, TestPackage $testPackage): bool
    {
        return $this->update($user, $testPackage) && $testPackage->attempts === 0;
    }

    public function transition(User $user, TestPackage $testPackage, string $toStatus): bool
    {
        return match ([$testPackage->status, $toStatus]) {
            ['Draf', 'Pemeriksaan'] => in_array($user->role, ['Owner', 'Admin', 'Penulis Konten'], true),
            ['Pemeriksaan', 'Draf'] => in_array($user->role, ['Owner', 'Admin', 'Reviewer'], true),
            ['Pemeriksaan', 'Disetujui'] => $testPackage->author_id !== $user->id
                && in_array($user->role, ['Owner', 'Admin', 'Reviewer'], true),
            ['Disetujui', 'Terbit'], ['Terbit', 'Arsip'] => in_array($user->role, ['Owner', 'Admin'], true),
            default => false,
        };
    }
}
