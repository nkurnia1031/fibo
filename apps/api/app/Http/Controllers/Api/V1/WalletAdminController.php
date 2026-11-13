<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccessOffer;
use App\Models\AuditLog;
use App\Models\PaymentMethod;
use App\Models\PointBundle;
use App\Models\PointTransaction;
use App\Models\StudentAccount;
use App\Models\TestPackage;
use App\Models\TopUpRequest;
use App\Models\WalletSetting;
use App\Services\PointWalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WalletAdminController extends Controller
{
    public function summary(): JsonResponse
    {
        Gate::authorize('viewAny', PaymentMethod::class);

        return response()->json([
            'confirmedTransferAmount' => (int) TopUpRequest::query()->where('status', 'Confirmed')->sum('transfer_amount'),
            'pendingTopUps' => TopUpRequest::query()->where('status', 'Pending')->count(),
            'totalTopUps' => TopUpRequest::query()->count(),
            'topUpPoints' => (int) PointTransaction::query()->where('type', 'topup_credit')->sum('points_delta'),
            'promotionalPoints' => (int) PointTransaction::query()->whereIn('type', ['signup_bonus', 'manual_grant'])->sum('points_delta'),
            'spentPoints' => (int) abs(PointTransaction::query()->where('type', 'purchase_debit')->sum('points_delta')),
            'correctedPoints' => (int) abs(PointTransaction::query()->where('type', 'manual_adjustment')->sum('points_delta')),
            'outstandingPoints' => (int) StudentAccount::query()->sum('point_balance'),
        ]);
    }

    public function settings(): JsonResponse
    {
        Gate::authorize('viewAny', PaymentMethod::class);
        $settings = WalletSetting::query()->firstOrCreate(['key' => 'default'], ['signup_bonus_points' => 100]);

        return response()->json(['signupBonusPoints' => $settings->signup_bonus_points]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        Gate::authorize('update', new PaymentMethod);
        $data = $request->validate([
            'signupBonusPoints' => ['required', 'integer', 'min:0', 'max:100000000'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $settings = WalletSetting::query()->firstOrCreate(['key' => 'default'], ['signup_bonus_points' => 100]);
        $before = ['signupBonusPoints' => $settings->signup_bonus_points];
        $settings->update(['signup_bonus_points' => $data['signupBonusPoints']]);
        AuditLog::record($request, 'wallet.settings_updated', 'WalletSetting', (string) $settings->id, $before, [
            'signupBonusPoints' => $settings->signup_bonus_points,
        ], $data['reason']);

        return response()->json(['signupBonusPoints' => $settings->signup_bonus_points]);
    }

    public function bundles()
    {
        Gate::authorize('viewAny', PaymentMethod::class);

        return response()->json(['data' => PointBundle::query()->orderBy('transfer_amount')->paginate(100)->through(fn (PointBundle $bundle): array => $this->bundleData($bundle))]);
    }

    public function storeBundle(Request $request): JsonResponse
    {
        Gate::authorize('create', PaymentMethod::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'transferAmount' => ['required', 'integer', 'min:1'],
            'points' => ['required', 'integer', 'min:1'],
            'bonusPoints' => ['sometimes', 'integer', 'min:0'],
            'isActive' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $bundle = PointBundle::query()->create([
            'code' => 'PB-'.Str::upper(Str::random(10)),
            'name' => $data['name'],
            'transfer_amount' => $data['transferAmount'],
            'points' => $data['points'],
            'bonus_points' => $data['bonusPoints'] ?? 0,
            'is_active' => $data['isActive'] ?? true,
            'created_by' => $request->user()->id,
        ]);
        $bundle->update(['code' => 'PB-'.str_pad((string) $bundle->id, 4, '0', STR_PAD_LEFT)]);
        AuditLog::record($request, 'point_bundle.created', 'PointBundle', (string) $bundle->id, null, $this->bundleData($bundle), $data['reason'] ?? 'Paket Top-up Poin dibuat');

        return response()->json(['data' => $this->bundleData($bundle)], 201);
    }

    public function updateBundle(Request $request, string $code): JsonResponse
    {
        $bundle = PointBundle::query()->where('code', $code)->firstOrFail();
        Gate::authorize('update', new PaymentMethod);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'transferAmount' => ['sometimes', 'required', 'integer', 'min:1'],
            'points' => ['sometimes', 'required', 'integer', 'min:1'],
            'bonusPoints' => ['sometimes', 'integer', 'min:0'],
            'isActive' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $before = $this->bundleData($bundle);
        $bundle->fill(array_filter([
            'name' => $data['name'] ?? null,
            'transfer_amount' => $data['transferAmount'] ?? null,
            'points' => $data['points'] ?? null,
            'bonus_points' => $data['bonusPoints'] ?? null,
            'is_active' => $data['isActive'] ?? null,
        ], fn ($value): bool => $value !== null));
        $bundle->save();
        AuditLog::record($request, 'point_bundle.updated', 'PointBundle', (string) $bundle->id, $before, $this->bundleData($bundle), $data['reason'] ?? 'Paket Top-up Poin diperbarui');

        return response()->json(['data' => $this->bundleData($bundle)]);
    }

    public function deleteBundle(Request $request, string $code)
    {
        $bundle = PointBundle::query()->where('code', $code)->firstOrFail();
        Gate::authorize('viewAny', PaymentMethod::class);
        abort_if(TopUpRequest::query()->where('point_bundle_id', $bundle->id)->exists(), 422, 'Paket yang sudah dipakai hanya dapat dinonaktifkan.');
        $before = $this->bundleData($bundle);
        $bundle->delete();
        AuditLog::record($request, 'point_bundle.deleted', 'PointBundle', (string) $bundle->id, $before, null, $request->string('reason')->toString() ?: 'Paket Top-up Poin dihapus');

        return response()->noContent();
    }

    public function offers()
    {
        Gate::authorize('viewAny', PaymentMethod::class);
        $offers = AccessOffer::query()->with('testPackage.series')->latest()->paginate(100);

        return response()->json(['data' => $offers->through(fn (AccessOffer $offer): array => $this->offerData($offer))]);
    }

    public function packageOptions(): JsonResponse
    {
        abort_unless(auth()->user()?->is_active && in_array(auth()->user()?->role, ['Owner', 'Admin', 'Finance'], true), 403);
        $packages = TestPackage::query()->with('series')->where('status', 'Terbit')->orderBy('name')->get();

        return response()->json(['data' => $packages->map(fn (TestPackage $package): array => [
            'id' => $package->id,
            'code' => $package->series?->code,
            'version' => $package->version,
            'name' => $package->name,
            'duration' => $package->duration_minutes,
        ])]);
    }

    public function storeOffer(Request $request): JsonResponse
    {
        Gate::authorize('create', PaymentMethod::class);
        $data = $this->validateOffer($request);
        $offer = AccessOffer::query()->create([
            'code' => 'AO-'.Str::upper(Str::random(10)),
            'product_type' => $data['productType'],
            'test_package_id' => $data['testPackageId'] ?? null,
            'material_code' => $data['materialCode'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price_points' => $data['pricePoints'],
            'validity_days' => $data['validityDays'] ?? null,
            'attempt_quota' => $data['attemptQuota'] ?? null,
            'is_active' => $data['isActive'] ?? true,
            'created_by' => $request->user()->id,
        ]);
        $offer->update(['code' => 'AO-'.str_pad((string) $offer->id, 4, '0', STR_PAD_LEFT)]);
        AuditLog::record($request, 'access_offer.created', 'AccessOffer', (string) $offer->id, null, $this->offerData($offer), $data['reason'] ?? 'Penawaran akses dibuat');

        return response()->json(['data' => $this->offerData($offer)], 201);
    }

    public function updateOffer(Request $request, string $code): JsonResponse
    {
        $offer = AccessOffer::query()->where('code', $code)->firstOrFail();
        Gate::authorize('update', new PaymentMethod);
        $data = $this->validateOffer($request, true);
        $before = $this->offerData($offer);
        $fields = [
            'productType' => 'product_type', 'testPackageId' => 'test_package_id',
            'materialCode' => 'material_code', 'title' => 'title', 'description' => 'description',
            'pricePoints' => 'price_points', 'validityDays' => 'validity_days',
            'attemptQuota' => 'attempt_quota', 'isActive' => 'is_active',
        ];
        $attributes = [];
        foreach ($fields as $requestField => $modelField) {
            if (array_key_exists($requestField, $data)) {
                $attributes[$modelField] = $data[$requestField];
            }
        }
        $offer->fill($attributes);
        $offer->save();
        AuditLog::record($request, 'access_offer.updated', 'AccessOffer', (string) $offer->id, $before, $this->offerData($offer->load('testPackage.series')), $data['reason'] ?? 'Penawaran akses diperbarui');

        return response()->json(['data' => $this->offerData($offer->load('testPackage.series'))]);
    }

    public function deleteOffer(Request $request, string $code)
    {
        $offer = AccessOffer::query()->where('code', $code)->firstOrFail();
        Gate::authorize('viewAny', PaymentMethod::class);
        abort_if($offer->studentAccesses()->exists(), 422, 'Penawaran yang sudah dibeli hanya dapat dinonaktifkan.');
        $before = $this->offerData($offer);
        $offer->delete();
        AuditLog::record($request, 'access_offer.deleted', 'AccessOffer', (string) $offer->id, $before, null, $request->string('reason')->toString() ?: 'Penawaran akses dihapus');

        return response()->noContent();
    }

    public function topUps(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PaymentMethod::class);
        $topups = TopUpRequest::query()->with('student')
            ->when($request->string('status')->isNotEmpty(), fn (Builder $query): Builder => $query->where('status', $request->string('status')->toString()))
            ->latest()->paginate(min(max($request->integer('per_page', 50), 1), 100));

        return response()->json(['data' => $topups->through(fn (TopUpRequest $topup): array => [
            ...$this->topUpData($topup),
            'student' => ['id' => 'M-'.str_pad((string) $topup->student_account_id, 4, '0', STR_PAD_LEFT), 'name' => $topup->student?->name, 'email' => $topup->student?->email],
        ])]);
    }

    public function reviewTopUp(Request $request, int $topupId, PointWalletService $wallet): JsonResponse
    {
        Gate::authorize('update', new PaymentMethod);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['Confirmed', 'Rejected'])],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $topup = DB::transaction(function () use ($request, $topupId, $data, $wallet): TopUpRequest {
            $topup = TopUpRequest::query()->with('student')->lockForUpdate()->findOrFail($topupId);
            if ($topup->status === 'Confirmed' && $data['decision'] === 'Confirmed') {
                return $topup;
            }
            abort_unless($topup->status === 'Pending', 409, 'Permintaan Top-up ini sudah diputuskan.');
            $before = ['status' => $topup->status];
            $topup->forceFill([
                'status' => $data['decision'],
                'reviewed_by' => $request->user()->id,
                'review_reason' => $data['reason'],
                'reviewed_at' => now(),
            ])->save();

            if ($data['decision'] === 'Confirmed') {
                $points = (int) $topup->points + (int) $topup->bonus_points;
                $wallet->credit(
                    $topup->student,
                    $points,
                    'topup_credit',
                    'Top-up dikonfirmasi: '.$topup->bundle_name,
                    'TopUpRequest',
                    (string) $topup->id,
                    'topup:'.$topup->id,
                    $request->user()->id,
                    ['transferAmount' => $topup->transfer_amount, 'bundle' => $topup->bundle_name],
                );
            }

            AuditLog::record($request, 'topup.reviewed', 'TopUpRequest', (string) $topup->id, $before, [
                'status' => $topup->status,
                'transferAmount' => $topup->transfer_amount,
                'pointsCredited' => $data['decision'] === 'Confirmed' ? $topup->points + $topup->bonus_points : 0,
            ], $data['reason']);

            return $topup;
        });

        return response()->json(['data' => [...$this->topUpData($topup), 'student' => [
            'id' => 'M-'.str_pad((string) $topup->student_account_id, 4, '0', STR_PAD_LEFT),
            'name' => $topup->student?->name,
            'email' => $topup->student?->email,
        ]]]);
    }

    public function proof(int $topupId)
    {
        Gate::authorize('viewAny', PaymentMethod::class);
        $topup = TopUpRequest::query()->findOrFail($topupId);
        abort_unless($topup->proof_path && Storage::disk('local')->exists($topup->proof_path), 404);

        return Storage::disk('local')->download($topup->proof_path, basename($topup->proof_path));
    }

    public function adjustPoints(Request $request, string $studentAccount, PointWalletService $wallet): JsonResponse
    {
        $student = StudentAccount::query()->findOrFail((int) str_replace('M-', '', $studentAccount));
        abort_unless(in_array($request->user()->role, ['Owner', 'Admin', 'Finance'], true) && $request->user()->is_active, 403);
        $data = $request->validate([
            'points' => ['required', 'integer', 'not_in:0', 'between:-100000000,100000000'],
            'reason' => ['required', 'string', 'max:500'],
            'idempotencyKey' => ['nullable', 'string', 'max:120'],
        ]);
        $rawKey = $data['idempotencyKey'] ?? $request->header('Idempotency-Key') ?? Str::ulid();
        if (! is_string($rawKey) || trim($rawKey) === '' || mb_strlen($rawKey) > 120) {
            throw ValidationException::withMessages(['idempotencyKey' => 'Idempotency-Key harus berupa teks maksimal 120 karakter.']);
        }
        $key = 'manual:'.hash('sha256', $student->id.':'.$rawKey);
        $before = (int) $student->point_balance;
        $transaction = $wallet->adjust($student, (int) $data['points'], $data['reason'], $key, $request->user()->id);
        AuditLog::record($request, 'wallet.adjusted', 'StudentAccount', (string) $student->id, ['balance' => $before], ['balance' => $transaction->balance_after, 'pointsDelta' => $transaction->points_delta], $data['reason']);

        return response()->json(['data' => $transaction, 'balance' => $transaction->balance_after]);
    }

    private function validateOffer(Request $request, bool $partial = false): array
    {
        $data = $request->validate([
            'productType' => [$partial ? 'sometimes' : 'required', Rule::in(['package', 'material'])],
            'testPackageId' => [$partial ? 'sometimes' : 'required_if:productType,package', 'nullable', 'integer', 'exists:test_packages,id'],
            'materialCode' => [$partial ? 'sometimes' : 'required_if:productType,material', 'nullable', 'string', 'max:80'],
            'title' => [$partial ? 'sometimes' : 'required', 'string', 'max:180'],
            'description' => ['sometimes', 'nullable', 'string', 'max:4000'],
            'pricePoints' => [$partial ? 'sometimes' : 'required', 'integer', 'min:1'],
            'validityDays' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:3650'],
            'attemptQuota' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000'],
            'isActive' => ['sometimes', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $type = $data['productType'] ?? null;
        if ($type === 'package' && empty($data['testPackageId'])) {
            abort(422, 'Pilih paket tes untuk penawaran paket.');
        }
        if ($type === 'material' && empty($data['materialCode'])) {
            abort(422, 'Kode materi wajib diisi untuk penawaran materi.');
        }

        return $data;
    }

    private function bundleData(PointBundle $bundle): array
    {
        return [
            'id' => $bundle->code,
            'name' => $bundle->name,
            'transferAmount' => $bundle->transfer_amount,
            'points' => $bundle->points,
            'bonusPoints' => $bundle->bonus_points,
            'isActive' => $bundle->is_active,
            'usage' => $bundle->topUpRequests()->count(),
        ];
    }

    private function offerData(AccessOffer $offer): array
    {
        return [
            'id' => $offer->code,
            'type' => $offer->product_type,
            'testPackageId' => $offer->test_package_id,
            'package' => $offer->testPackage?->name,
            'materialCode' => $offer->material_code,
            'title' => $offer->title,
            'description' => $offer->description,
            'pricePoints' => $offer->price_points,
            'validityDays' => $offer->validity_days,
            'attemptQuota' => $offer->attempt_quota,
            'isActive' => $offer->is_active,
            'usage' => $offer->studentAccesses()->count(),
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
