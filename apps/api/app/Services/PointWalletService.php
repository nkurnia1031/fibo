<?php

namespace App\Services;

use App\Models\PointTransaction;
use App\Models\StudentAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PointWalletService
{
    public function adjust(
        StudentAccount $student,
        int $delta,
        string $reason,
        string $idempotencyKey,
        int $actorId,
    ): PointTransaction {
        if ($delta === 0) {
            throw ValidationException::withMessages(['points' => 'Nilai penyesuaian tidak boleh nol.']);
        }

        return DB::transaction(function () use ($student, $delta, $reason, $idempotencyKey, $actorId): PointTransaction {
            $existing = PointTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                abort_unless($existing->student_account_id === $student->id && $existing->points_delta === $delta, 409, 'Idempotency-Key sudah digunakan untuk transaksi lain.');

                return $existing;
            }

            $lockedStudent = StudentAccount::query()->lockForUpdate()->findOrFail($student->id);
            $existing = PointTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                abort_unless($existing->student_account_id === $student->id && $existing->points_delta === $delta, 409, 'Idempotency-Key sudah digunakan untuk transaksi lain.');

                return $existing;
            }
            if ($delta < 0 && $lockedStudent->point_balance < abs($delta)) {
                throw ValidationException::withMessages(['points' => 'Penyesuaian akan membuat saldo Poin negatif.']);
            }

            $lockedStudent->point_balance += $delta;
            $lockedStudent->save();

            return PointTransaction::query()->create([
                'student_account_id' => $lockedStudent->id,
                'type' => $delta > 0 ? 'manual_grant' : 'manual_adjustment',
                'points_delta' => $delta,
                'balance_after' => $lockedStudent->point_balance,
                'source_type' => 'StaffAdjustment',
                'source_id' => (string) $actorId,
                'idempotency_key' => $idempotencyKey,
                'reason' => $reason,
                'actor_id' => $actorId,
            ]);
        });
    }

    public function credit(
        StudentAccount $student,
        int $points,
        string $type,
        string $reason,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?string $idempotencyKey = null,
        ?int $actorId = null,
        array $metadata = [],
    ): PointTransaction {
        if ($points <= 0) {
            throw ValidationException::withMessages(['points' => 'Jumlah Poin harus lebih dari nol.']);
        }

        return DB::transaction(function () use ($student, $points, $type, $reason, $sourceType, $sourceId, $idempotencyKey, $actorId, $metadata): PointTransaction {
            if ($idempotencyKey) {
                $existing = PointTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    abort_unless($existing->student_account_id === $student->id && $existing->points_delta === $points, 409, 'Idempotency-Key sudah digunakan untuk transaksi lain.');

                    return $existing;
                }
            }

            $lockedStudent = StudentAccount::query()->lockForUpdate()->findOrFail($student->id);
            if ($idempotencyKey) {
                $existing = PointTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    abort_unless($existing->student_account_id === $student->id && $existing->points_delta === $points, 409, 'Idempotency-Key sudah digunakan untuk transaksi lain.');

                    return $existing;
                }
            }
            $lockedStudent->point_balance += $points;
            $lockedStudent->save();

            return PointTransaction::query()->create([
                'student_account_id' => $lockedStudent->id,
                'type' => $type,
                'points_delta' => $points,
                'balance_after' => $lockedStudent->point_balance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'reason' => $reason,
                'metadata' => $metadata ?: null,
                'actor_id' => $actorId,
            ]);
        });
    }

    public function debit(
        StudentAccount $student,
        int $points,
        string $type,
        string $reason,
        string $sourceType,
        string $sourceId,
        string $idempotencyKey,
        array $metadata = [],
    ): PointTransaction {
        if ($points <= 0) {
            throw ValidationException::withMessages(['points' => 'Jumlah Poin harus lebih dari nol.']);
        }

        return DB::transaction(function () use ($student, $points, $type, $reason, $sourceType, $sourceId, $idempotencyKey, $metadata): PointTransaction {
            $existing = PointTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                abort_unless($existing->student_account_id === $student->id && $existing->points_delta === -$points, 409, 'Idempotency-Key sudah digunakan untuk transaksi lain.');

                return $existing;
            }

            $lockedStudent = StudentAccount::query()->lockForUpdate()->findOrFail($student->id);
            $existing = PointTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                abort_unless($existing->student_account_id === $student->id && $existing->points_delta === -$points, 409, 'Idempotency-Key sudah digunakan untuk transaksi lain.');

                return $existing;
            }
            if ($lockedStudent->point_balance < $points) {
                throw ValidationException::withMessages(['points' => 'Saldo Poin tidak mencukupi.']);
            }

            $lockedStudent->point_balance -= $points;
            $lockedStudent->save();

            return PointTransaction::query()->create([
                'student_account_id' => $lockedStudent->id,
                'type' => $type,
                'points_delta' => -$points,
                'balance_after' => $lockedStudent->point_balance,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'reason' => $reason,
                'metadata' => $metadata ?: null,
            ]);
        });
    }
}
