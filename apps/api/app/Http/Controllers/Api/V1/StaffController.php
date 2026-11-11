<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminUserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', User::class);

        return AdminUserResource::collection(User::query()->latest()->paginate(100));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', User::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['Owner', 'Admin', 'Penulis Konten', 'Reviewer', 'Finance', 'Support'])],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $temporaryPassword = Str::password(20);
        $staff = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => $temporaryPassword,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        AuditLog::record($request, 'staff.created', 'User', (string) $staff->id, null, $this->auditState($staff), $data['reason'] ?? 'Akun staf diundang');

        return response()->json([
            'data' => AdminUserResource::make($staff),
            'temporaryPassword' => $temporaryPassword,
        ], 201);
    }

    public function update(Request $request, string $staffCode)
    {
        $staff = $this->findStaff($staffCode);
        Gate::authorize('update', $staff);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->id)],
            'role' => ['sometimes', Rule::in(['Owner', 'Admin', 'Penulis Konten', 'Reviewer', 'Finance', 'Support'])],
            'isActive' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $this->protectLastOwner($staff, $data);
        $before = $this->auditState($staff);

        $staff->fill(array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'role' => $data['role'] ?? null,
            'is_active' => array_key_exists('isActive', $data) ? $data['isActive'] : null,
        ], fn ($value): bool => $value !== null));
        $staff->save();

        if (! $staff->is_active) {
            DB::table('sessions')->where('user_id', $staff->id)->delete();
        }

        AuditLog::record($request, 'staff.updated', 'User', (string) $staff->id, $before, $this->auditState($staff), $data['reason'] ?? 'Peran atau akses staf diperbarui');

        return AdminUserResource::make($staff->refresh());
    }

    public function destroy(Request $request, string $staffCode)
    {
        $staff = $this->findStaff($staffCode);
        Gate::authorize('delete', $staff);
        $before = $this->auditState($staff);
        AuditLog::record($request, 'staff.invitation_deleted', 'User', (string) $staff->id, $before, null, $request->string('reason')->toString() ?: 'Undangan staf dihapus');
        $staff->delete();

        return response()->noContent();
    }

    public function resetPassword(Request $request, string $staffCode)
    {
        $staff = $this->findStaff($staffCode);
        Gate::authorize('update', $staff);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $temporaryPassword = Str::password(20);
        $before = $this->auditState($staff);
        $staff->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ])->save();
        DB::table('sessions')->where('user_id', $staff->id)->delete();

        AuditLog::record($request, 'staff.password_reset', 'User', (string) $staff->id, $before, $this->auditState($staff), $data['reason'] ?? 'Kredensial staf direset');

        return response()->json(['temporaryPassword' => $temporaryPassword]);
    }

    private function protectLastOwner(User $staff, array $data): void
    {
        $losesOwnerAccess = $staff->role === 'Owner'
            && $staff->is_active
            && (($data['role'] ?? 'Owner') !== 'Owner' || ! ($data['isActive'] ?? true));

        if ($losesOwnerAccess && User::query()->where('role', 'Owner')->where('is_active', true)->count() <= 1) {
            abort(422, 'Organisasi harus memiliki setidaknya satu Owner aktif.');
        }
    }

    private function findStaff(string $code): User
    {
        if (! preg_match('/^U-(\d+)$/', $code, $matches)) {
            abort(404);
        }

        return User::query()->findOrFail((int) $matches[1]);
    }

    private function auditState(User $staff): array
    {
        return [
            'name' => $staff->name,
            'email' => $staff->email,
            'role' => $staff->role,
            'isActive' => $staff->is_active,
            'mustChangePassword' => $staff->must_change_password,
        ];
    }
}
