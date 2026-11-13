<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentAccountResource;
use App\Models\AuditLog;
use App\Models\StudentAccount;
use App\Models\WalletSetting;
use App\Services\PointWalletService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class StudentAuthController extends Controller
{
    public function register(Request $request, PointWalletService $wallet): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:190', 'unique:student_accounts,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);

        $student = StudentAccount::query()->create([
            'external_id' => 'self-'.Str::lower((string) Str::ulid()),
            'name' => trim($data['name']),
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'status' => 'Aktif',
        ]);

        $bonus = (int) WalletSetting::query()->firstOrCreate(['key' => 'default'], ['signup_bonus_points' => 100])->signup_bonus_points;
        if ($bonus > 0) {
            $wallet->credit(
                $student,
                $bonus,
                'signup_bonus',
                'Bonus pendaftaran murid',
                'StudentAccount',
                (string) $student->id,
                'signup:'.$student->id,
                metadata: ['source' => 'registration'],
            );
        }

        $student->sendEmailVerificationNotification();
        AuditLog::record($request, 'student.registered', 'StudentAccount', (string) $student->id, null, [
            'name' => $student->name,
            'email' => $student->email,
            'signupBonusPoints' => $bonus,
        ], 'Pendaftaran mandiri murid', $student->id);

        return response()->json([
            'message' => 'Akun berhasil dibuat. Periksa email untuk verifikasi sebelum membeli akses atau mengajukan Top-up.',
            'data' => StudentAccountResource::make($student->refresh()),
        ], 201);
    }

    public function verify(Request $request, int $student, string $hash): JsonResponse
    {
        $account = StudentAccount::query()->findOrFail($student);
        abort_unless(hash_equals(sha1($account->getEmailForVerification()), $hash), 403, 'Tautan verifikasi tidak valid.');

        if (! $account->hasVerifiedEmail()) {
            $account->markEmailAsVerified();
            AuditLog::record($request, 'student.email_verified', 'StudentAccount', (string) $account->id, null, [
                'emailVerifiedAt' => $account->email_verified_at?->toISOString(),
            ], 'Email murid diverifikasi');
        }

        return response()->json(['message' => 'Email berhasil diverifikasi. Kamu dapat masuk ke Fibo.']);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $student = $request->user('student');
        if ($student && ! $student->hasVerifiedEmail()) {
            $student->sendEmailVerificationNotification();
        }

        return response()->json(['message' => 'Jika akun belum terverifikasi, tautan verifikasi baru telah dikirim.']);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credentials['status'] = 'Aktif';

        if (! Auth::guard('student')->attempt($credentials)) {
            return response()->json(['message' => 'Email atau kata sandi tidak cocok.'], 422);
        }

        $request->session()->regenerate();
        $student = $request->user('student');

        return response()->json([
            'data' => StudentAccountResource::make($student),
            'emailVerified' => $student->hasVerifiedEmail(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => StudentAccountResource::make($request->user('student'))]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('student')->logout();
        $request->session()->regenerate();

        return response()->json(['message' => 'Kamu telah keluar.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::broker('students')->sendResetLink(['email' => Str::lower($request->string('email')->toString())]);

        return response()->json(['message' => 'Jika email terdaftar, instruksi reset kata sandi telah dikirim.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);

        $status = Password::broker('students')->reset(
            [...$data, 'email' => Str::lower($data['email'])],
            function (StudentAccount $student, string $password) use ($request): void {
                $oldStatus = $student->status;
                $student->forceFill([
                    'password' => $password,
                    'status' => $student->status === 'Undangan' ? 'Aktif' : $student->status,
                    'email_verified_at' => $student->status === 'Undangan' ? ($student->email_verified_at ?? now()) : $student->email_verified_at,
                ])->setRememberToken(Str::random(60));
                $student->save();
                AuditLog::record($request, 'student.password_reset', 'StudentAccount', (string) $student->id, ['status' => $oldStatus], [
                    'status' => $student->status,
                    'emailVerified' => $student->hasVerifiedEmail(),
                ], 'Murid mengatur ulang kata sandi');
                event(new PasswordReset($student));
            },
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Kata sandi berhasil diperbarui.'])
            : response()->json(['message' => 'Tautan reset tidak valid atau sudah kedaluwarsa.'], 422);
    }
}
