<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccessOffer;
use App\Models\AuditLog;
use App\Models\PaymentMethod;
use App\Models\PointBundle;
use App\Models\PointTransaction;
use App\Models\StudentAccess;
use App\Models\StudentAccount;
use App\Models\TopUpRequest;
use App\Services\PointWalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StudentWalletController extends Controller
{
    public function catalog()
    {
        $offers = AccessOffer::query()
            ->with(['testPackage.items.question'])
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->where('product_type', 'material')
                    ->orWhereHas('testPackage', fn (Builder $package): Builder => $package->where('status', 'Terbit'));
            })
            ->orderBy('price_points')
            ->get();

        return response()->json(['data' => $offers->map(fn (AccessOffer $offer): array => $this->offerData($offer))]);
    }

    public function topUpOptions()
    {
        return response()->json([
            'bundles' => PointBundle::query()->where('is_active', true)->orderBy('transfer_amount')->get()->map(fn (PointBundle $bundle): array => [
                'id' => $bundle->code,
                'name' => $bundle->name,
                'transferAmount' => $bundle->transfer_amount,
                'points' => $bundle->points,
                'bonusPoints' => $bundle->bonus_points,
                'totalPoints' => $bundle->points + $bundle->bonus_points,
            ]),
            'paymentMethods' => PaymentMethod::query()->where('is_active', true)->orderBy('name')->get()->map(fn (PaymentMethod $method): array => [
                'id' => $method->code,
                'name' => $method->name,
                'type' => $method->type,
                'provider' => $method->provider,
                'accountName' => $method->account_name,
                'accountNumber' => $method->account_number,
                'instructions' => $method->instructions,
            ]),
        ]);
    }

    public function wallet(Request $request): JsonResponse
    {
        $student = $request->user('student');

        return response()->json([
            'balance' => (int) $student->point_balance,
            'transactions' => PointTransaction::query()->where('student_account_id', $student->id)->latest('id')->paginate(30),
        ]);
    }

    public function topUps(Request $request): JsonResponse
    {
        return response()->json([
            'data' => TopUpRequest::query()->where('student_account_id', $request->user('student')->id)->latest()->paginate(30)->through(fn (TopUpRequest $topup): array => $this->topUpData($topup)),
        ]);
    }

    public function requestTopUp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bundleId' => ['required', 'string', 'exists:point_bundles,code'],
            'paymentMethodId' => ['required', 'string', 'exists:payment_methods,code'],
            'paymentReference' => ['required', 'string', 'max:160'],
            'idempotencyKey' => ['nullable', 'string', 'max:120'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $student = $request->user('student');
        $idempotencyKey = $data['idempotencyKey'] ?? $request->header('Idempotency-Key');
        if (! is_string($idempotencyKey) || trim($idempotencyKey) === '' || mb_strlen($idempotencyKey) > 120) {
            throw ValidationException::withMessages(['idempotencyKey' => 'Idempotency-Key wajib berupa teks maksimal 120 karakter agar pengajuan Top-up aman diulang.']);
        }
        $idempotencyKey = trim($idempotencyKey);
        $existing = TopUpRequest::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            abort_unless(
                $existing->student_account_id === $student->id
                && $existing->point_bundle_id === PointBundle::query()->where('code', $data['bundleId'])->value('id')
                && $existing->payment_method_id === PaymentMethod::query()->where('code', $data['paymentMethodId'])->value('id')
                && $existing->payment_reference === $data['paymentReference'],
                409,
                'Idempotency-Key sudah digunakan untuk pengajuan lain.',
            );

            return response()->json(['data' => $this->topUpData($existing)]);
        }
        $bundle = PointBundle::query()->where('code', $data['bundleId'])->where('is_active', true)->firstOrFail();
        $method = PaymentMethod::query()->where('code', $data['paymentMethodId'])->where('is_active', true)->firstOrFail();
        $proofPath = $request->file('proof')->store('topups/'.$student->id, 'local');

        $topup = DB::transaction(function () use ($student, $bundle, $method, $data, $proofPath, $idempotencyKey): TopUpRequest {
            StudentAccount::query()->lockForUpdate()->findOrFail($student->id);
            $existing = TopUpRequest::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                abort_unless(
                    $existing->student_account_id === $student->id
                    && $existing->point_bundle_id === $bundle->id
                    && $existing->payment_method_id === $method->id
                    && $existing->payment_reference === $data['paymentReference'],
                    409,
                    'Idempotency-Key sudah digunakan untuk pengajuan lain.',
                );

                return $existing;
            }
            $topup = TopUpRequest::query()->create([
                'student_account_id' => $student->id,
                'point_bundle_id' => $bundle->id,
                'payment_method_id' => $method->id,
                'bundle_name' => $bundle->name,
                'transfer_amount' => $bundle->transfer_amount,
                'points' => $bundle->points,
                'bonus_points' => $bundle->bonus_points,
                'payment_method_name' => $method->name,
                'payment_details' => [
                    'type' => $method->type,
                    'provider' => $method->provider,
                    'accountName' => $method->account_name,
                    'accountNumber' => $method->account_number,
                    'instructions' => $method->instructions,
                ],
                'payment_reference' => $data['paymentReference'],
                'proof_path' => $proofPath,
                'idempotency_key' => $idempotencyKey,
                'status' => 'Pending',
            ]);
            $method->increment('usage_count');

            return $topup;
        });

        if (! $topup->wasRecentlyCreated) {
            Storage::disk('local')->delete($proofPath);

            return response()->json(['data' => $this->topUpData($topup)]);
        }

        AuditLog::record($request, 'topup.requested', 'TopUpRequest', (string) $topup->id, null, $this->topUpData($topup), 'Murid mengajukan Top-up Poin');

        return response()->json(['data' => $this->topUpData($topup)], 201);
    }

    public function accesses(Request $request): JsonResponse
    {
        $accesses = StudentAccess::query()->with('testPackage.items.question.series')
            ->where('student_account_id', $request->user('student')->id)->latest()->get();

        return response()->json(['data' => $accesses->map(fn (StudentAccess $access): array => $this->accessData($access))]);
    }

    public function purchase(Request $request, string $code, PointWalletService $wallet): JsonResponse
    {
        $data = $request->validate(['idempotencyKey' => ['nullable', 'string', 'max:120']]);
        $idempotencyKey = $data['idempotencyKey'] ?? $request->header('Idempotency-Key');
        if (! is_string($idempotencyKey) || trim($idempotencyKey) === '' || mb_strlen($idempotencyKey) > 120) {
            throw ValidationException::withMessages(['idempotencyKey' => 'Idempotency-Key wajib berupa teks maksimal 120 karakter agar pembelian aman diulang.']);
        }
        $idempotencyKey = trim($idempotencyKey);
        $student = $request->user('student');
        $existing = StudentAccess::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            abort_unless(
                $existing->student_account_id === $student->id
                && AccessOffer::query()->where('code', $code)->value('id') === $existing->access_offer_id,
                409,
                'Idempotency-Key telah dipakai untuk pembelian lain.',
            );

            return response()->json(['data' => $this->accessData($existing->load('testPackage.items.question.series'))]);
        }
        $offer = AccessOffer::query()->where('code', $code)->where('is_active', true)->firstOrFail();

        $access = DB::transaction(function () use ($student, $offer, $idempotencyKey, $wallet): StudentAccess {
            $duplicate = StudentAccess::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($duplicate) {
                abort_unless($duplicate->student_account_id === $student->id && $duplicate->access_offer_id === $offer->id, 409, 'Idempotency-Key telah dipakai untuk pembelian lain.');

                return $duplicate->load('testPackage.items.question.series');
            }

            StudentAccount::query()->lockForUpdate()->findOrFail($student->id);
            $lockedOffer = AccessOffer::query()->with('testPackage')->lockForUpdate()->findOrFail($offer->id);
            $duplicate = StudentAccess::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($duplicate) {
                abort_unless($duplicate->student_account_id === $student->id && $duplicate->access_offer_id === $offer->id, 409, 'Idempotency-Key telah dipakai untuk pembelian lain.');

                return $duplicate->load('testPackage.items.question.series');
            }
            abort_unless($lockedOffer->is_active, 422, 'Penawaran ini sudah tidak aktif.');
            abort_if($lockedOffer->product_type === 'package' && $lockedOffer->testPackage?->status !== 'Terbit', 422, 'Paket ini belum tersedia.');

            $access = StudentAccess::query()->create([
                'student_account_id' => $student->id,
                'access_offer_id' => $lockedOffer->id,
                'test_package_id' => $lockedOffer->test_package_id,
                'material_code' => $lockedOffer->material_code,
                'offer_title' => $lockedOffer->title,
                'price_points' => $lockedOffer->price_points,
                'attempt_quota' => $lockedOffer->attempt_quota,
                'starts_at' => now(),
                'expires_at' => $lockedOffer->validity_days ? now()->addDays($lockedOffer->validity_days) : null,
                'status' => 'Aktif',
                'idempotency_key' => $idempotencyKey,
            ]);
            $wallet->debit(
                $student,
                (int) $lockedOffer->price_points,
                'purchase_debit',
                'Pembelian akses: '.$lockedOffer->title,
                'StudentAccess',
                (string) $access->id,
                'purchase:'.hash('sha256', $idempotencyKey),
                ['offerCode' => $lockedOffer->code, 'pricePoints' => $lockedOffer->price_points],
            );

            return $access->load('testPackage.items.question.series');
        });

        AuditLog::record($request, 'access.purchased', 'StudentAccess', (string) $access->id, null, [
            'offer' => $access->offer_title,
            'pricePoints' => $access->price_points,
            'expiresAt' => $access->expires_at?->toISOString(),
        ], 'Murid membeli akses menggunakan Poin');

        return response()->json(['data' => $this->accessData($access)], 201);
    }

    private function offerData(AccessOffer $offer): array
    {
        return [
            'id' => $offer->code,
            'type' => $offer->product_type,
            'title' => $offer->title,
            'description' => $offer->description,
            'pricePoints' => $offer->price_points,
            'validityDays' => $offer->validity_days,
            'attemptQuota' => $offer->attempt_quota,
            'package' => $offer->testPackage ? [
                'id' => $offer->testPackage->series?->code,
                'version' => $offer->testPackage->version,
                'name' => $offer->testPackage->name,
                'duration' => $offer->testPackage->duration_minutes,
                'questions' => $offer->testPackage->items->count(),
            ] : null,
        ];
    }

    private function accessData(StudentAccess $access): array
    {
        $status = $access->status;
        if ($status === 'Aktif' && $access->expires_at?->isPast()) {
            $status = 'Kedaluwarsa';
        } elseif ($status === 'Aktif' && $access->attempt_quota !== null && $access->attempts_used >= $access->attempt_quota) {
            $status = 'Kuota habis';
        }

        return [
            'id' => $access->id,
            'title' => $access->offer_title,
            'pricePoints' => $access->price_points,
            'attemptQuota' => $access->attempt_quota,
            'attemptsUsed' => $access->attempts_used,
            'startsAt' => $access->starts_at?->toISOString(),
            'expiresAt' => $access->expires_at?->toISOString(),
            'status' => $status,
            'package' => $access->testPackage ? [
                'id' => $access->testPackage->series?->code,
                'version' => $access->testPackage->version,
                'name' => $access->testPackage->name,
                'duration' => $access->testPackage->duration_minutes,
            ] : null,
        ];
    }

    private function topUpData(TopUpRequest $topup): array
    {
        return [
            'id' => $topup->id,
            'bundle' => $topup->bundle_name,
            'transferAmount' => $topup->transfer_amount,
            'points' => $topup->points,
            'bonusPoints' => $topup->bonus_points,
            'paymentMethod' => $topup->payment_method_name,
            'paymentDetails' => $topup->payment_details,
            'paymentReference' => $topup->payment_reference,
            'status' => $topup->status,
            'reviewReason' => $topup->review_reason,
            'reviewedAt' => $topup->reviewed_at?->toISOString(),
            'createdAt' => $topup->created_at?->toISOString(),
        ];
    }
}
