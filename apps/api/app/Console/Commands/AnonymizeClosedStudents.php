<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\PointTransaction;
use App\Models\StudentAccount;
use App\Models\TopUpRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AnonymizeClosedStudents extends Command
{
    protected $signature = 'students:anonymize-closed';

    protected $description = 'Anonymize closed student accounts after their configured retention period';

    public function handle(): int
    {
        $retentionDays = (int) config('privacy.student_data_retention_days', 730);
        if ($retentionDays <= 0) {
            $this->info('Student account anonymization is disabled by configuration.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($retentionDays);
        $anonymized = 0;
        $failures = 0;

        StudentAccount::query()
            ->where('status', 'Ditutup')
            ->whereNotNull('closed_at')
            ->where('closed_at', '<=', $cutoff)
            ->whereNull('anonymized_at')
            ->select('id')
            ->chunkById(100, function ($students) use ($cutoff, &$anonymized): void {
                foreach ($students as $candidate) {
                    try {
                        $didAnonymize = DB::transaction(function () use ($candidate, $cutoff): bool {
                            $student = StudentAccount::query()->lockForUpdate()->find($candidate->id);
                            if (! $student || $student->status !== 'Ditutup' || $student->anonymized_at || ! $student->closed_at?->lessThanOrEqualTo($cutoff)) {
                                return false;
                            }

                            $email = $student->email;
                            $proofPaths = TopUpRequest::query()
                                ->where('student_account_id', $student->id)
                                ->whereNotNull('proof_path')
                                ->pluck('proof_path')
                                ->all();

                            $disk = Storage::disk('local');
                            foreach ($proofPaths as $proofPath) {
                                if ($disk->exists($proofPath) && ! $disk->delete($proofPath)) {
                                    throw new \RuntimeException('Could not delete a retained student top-up proof.');
                                }
                            }

                            DB::table('student_password_reset_tokens')->where('email', $email)->delete();
                            TopUpRequest::query()->where('student_account_id', $student->id)->update([
                                'payment_reference' => null,
                                'proof_path' => null,
                                'review_reason' => 'Catatan dipertahankan setelah anonimisasi murid.',
                            ]);
                            PointTransaction::query()->where('student_account_id', $student->id)->update([
                                'reason' => 'Transaksi dipertahankan setelah anonimisasi murid.',
                                'metadata' => null,
                            ]);

                            AuditLog::query()
                                ->where(fn ($query) => $query
                                    ->where(fn ($subject) => $subject->where('subject_type', 'StudentAccount')->where('subject_id', (string) $student->id))
                                    ->orWhere('student_actor_id', $student->id))
                                ->get()
                                ->each(function (AuditLog $audit): void {
                                    $audit->forceFill([
                                        'before' => $this->withoutIdentity($audit->before),
                                        'after' => $this->withoutIdentity($audit->after),
                                        'reason' => 'Catatan dipertahankan setelah anonimisasi murid.',
                                        'ip_address' => null,
                                    ])->save();
                                });

                            $student->forceFill([
                                'name' => 'Murid anonim '.$student->id,
                                'email' => 'anonim-murid-'.$student->id.'@example.invalid',
                                'school' => null,
                                'external_id' => 'anonim-murid-'.$student->id,
                                'password' => Str::random(64),
                                'email_verified_at' => null,
                                'remember_token' => Str::random(60),
                                'anonymized_at' => now(),
                            ])->save();

                            AuditLog::query()->create([
                                'action' => 'student.anonymized',
                                'subject_type' => 'StudentAccount',
                                'subject_id' => (string) $student->id,
                                'before' => ['status' => 'Ditutup', 'anonymized' => false],
                                'after' => ['status' => 'Ditutup', 'anonymized' => true],
                                'reason' => 'Masa retensi akun murid berakhir.',
                            ]);

                            return true;
                        });
                    } catch (Throwable $exception) {
                        $failures++;
                        Log::error('Failed to anonymize a closed student account.', [
                            'student_account_id' => $candidate->id,
                            'exception_type' => $exception::class,
                        ]);

                        continue;
                    }

                    if ($didAnonymize) {
                        $anonymized++;
                    }
                }
            });

        $this->info("Anonymized {$anonymized} closed student account(s); {$failures} failed.");

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** @param array<string, mixed>|null $state
     * @return array<string, mixed>|null
     */
    private function withoutIdentity(?array $state): ?array
    {
        if ($state === null) {
            return null;
        }

        unset($state['name'], $state['email'], $state['school'], $state['externalId']);

        return $state;
    }
}
