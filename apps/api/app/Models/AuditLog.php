<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

#[Fillable(['actor_id', 'action', 'subject_type', 'subject_id', 'before', 'after', 'reason', 'ip_address'])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }

    public static function record(
        Request $request,
        string $action,
        string $subjectType,
        string $subjectId,
        ?array $before,
        ?array $after,
        string $reason,
    ): self {
        return static::query()->create([
            'actor_id' => $request->user()?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'ip_address' => $request->ip(),
        ]);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
