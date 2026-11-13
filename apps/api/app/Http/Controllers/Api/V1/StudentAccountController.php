<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentAccountRequest;
use App\Http\Requests\UpdateStudentAccountRequest;
use App\Http\Resources\StudentAccountResource;
use App\Models\AuditLog;
use App\Models\StudentAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class StudentAccountController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', StudentAccount::class);

        $students = StudentAccount::query()
            ->withCount(['accesses as active_accesses_count' => fn ($query) => $query
                ->where('status', 'Aktif')
                ->where(fn ($access) => $access->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->where(fn ($access) => $access->whereNull('student_accesses.attempt_quota')->orWhereColumn('student_accesses.attempts_used', '<', 'student_accesses.attempt_quota'))])
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($builder) => $builder->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('school', 'like', $term));
            })
            ->latest()
            ->paginate(min(max($request->integer('per_page', 100), 1), 100));

        return StudentAccountResource::collection($students);
    }

    public function store(StoreStudentAccountRequest $request): StudentAccountResource
    {
        Gate::authorize('create', StudentAccount::class);
        $data = $request->validated();
        $student = StudentAccount::create([
            'external_id' => ($data['externalId'] ?? null) ?: 'invite-'.Str::ulid(),
            'name' => $data['name'],
            'email' => $data['email'],
            'school' => $data['school'] ?? null,
            'status' => 'Undangan',
            'created_by' => $request->user()->id,
        ]);

        Password::broker('students')->sendResetLink(['email' => $student->email]);

        AuditLog::record($request, 'student.created', 'StudentAccount', (string) $student->id, null, $this->auditState($student), $data['reason'] ?? 'Akun murid diprovisi');

        return StudentAccountResource::make($student);
    }

    public function update(UpdateStudentAccountRequest $request, string $studentAccount): StudentAccountResource
    {
        $student = $this->findStudent($studentAccount);
        Gate::authorize('update', $student);
        abort_if($student->status === 'Ditutup', 422, 'Akun yang sudah ditutup tidak dapat diubah.');
        $data = $request->validated();
        $before = $this->auditState($student);
        $emailChanged = isset($data['email']) && $data['email'] !== $student->email;
        $student->fill(array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'school' => array_key_exists('school', $data) ? $data['school'] : null,
            'external_id' => array_key_exists('externalId', $data) ? ($data['externalId'] ?: 'invite-'.Str::ulid()) : null,
            'status' => $data['status'] ?? null,
        ], fn ($value): bool => $value !== null));
        if ($emailChanged) {
            $student->markEmailAsUnverified();
        }
        $student->save();

        AuditLog::record($request, 'student.updated', 'StudentAccount', (string) $student->id, $before, $this->auditState($student), $data['reason'] ?? 'Data atau akses murid diperbarui');

        return StudentAccountResource::make($student);
    }

    public function destroy(Request $request, string $studentAccount)
    {
        $student = $this->findStudent($studentAccount);
        Gate::authorize('delete', $student);
        AuditLog::record($request, 'student.invitation_deleted', 'StudentAccount', (string) $student->id, $this->auditState($student), null, $request->string('reason')->toString() ?: 'Undangan murid dihapus');
        $student->delete();

        return response()->noContent();
    }

    public function close(Request $request, string $studentAccount): StudentAccountResource
    {
        $student = $this->findStudent($studentAccount);
        Gate::authorize('update', $student);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $before = $this->auditState($student);
        $student->forceFill(['status' => 'Ditutup', 'closed_at' => now(), 'remember_token' => Str::random(60)])->save();

        AuditLog::record($request, 'student.closed', 'StudentAccount', (string) $student->id, $before, $this->auditState($student), $data['reason']);

        return StudentAccountResource::make($student);
    }

    public function resetCredential(Request $request, string $studentAccount)
    {
        $student = $this->findStudent($studentAccount);
        Gate::authorize('update', $student);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        Password::broker('students')->sendResetLink(['email' => $student->email]);
        AuditLog::record($request, 'student.credential_reset_requested', 'StudentAccount', (string) $student->id, $this->auditState($student), $this->auditState($student), $data['reason']);

        return response()->json([
            'message' => 'Tautan reset kata sandi dikirim ke email murid.',
        ], 202);
    }

    private function findStudent(string $code): StudentAccount
    {
        if (! preg_match('/^M-(\d+)$/', $code, $matches)) {
            abort(404);
        }

        return StudentAccount::query()->findOrFail((int) $matches[1]);
    }

    private function auditState(StudentAccount $student): array
    {
        return [
            'name' => $student->name,
            'email' => $student->email,
            'school' => $student->school,
            'externalId' => $student->external_id,
            'status' => $student->status,
            'attemptsUsed' => $student->attempts_used,
        ];
    }
}
