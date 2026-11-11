<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Penulis Konten', 'Reviewer'], true);
    }

    public function create(User $user): bool
    {
        return $user->is_active && in_array($user->role, ['Owner', 'Admin', 'Penulis Konten'], true);
    }

    public function update(User $user, Question $question): bool
    {
        return $question->status === 'Draf'
            && ($question->author_id === $user->id || in_array($user->role, ['Owner', 'Admin'], true));
    }

    public function delete(User $user, Question $question): bool
    {
        return $this->update($user, $question) && ! $question->packageItems()->exists();
    }

    public function transition(User $user, Question $question, string $toStatus): bool
    {
        return match ([$question->status, $toStatus]) {
            ['Draf', 'Pemeriksaan'] => in_array($user->role, ['Owner', 'Admin', 'Penulis Konten'], true),
            ['Pemeriksaan', 'Draf'] => in_array($user->role, ['Owner', 'Admin', 'Reviewer'], true),
            ['Pemeriksaan', 'Disetujui'] => $question->author_id !== $user->id
                && in_array($user->role, ['Owner', 'Admin', 'Reviewer'], true),
            ['Disetujui', 'Terbit'], ['Terbit', 'Arsip'] => in_array($user->role, ['Owner', 'Admin'], true),
            default => false,
        };
    }
}
