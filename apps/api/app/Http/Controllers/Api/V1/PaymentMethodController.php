<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\AuditLog;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', PaymentMethod::class);

        return PaymentMethodResource::collection(PaymentMethod::query()->orderBy('name')->paginate(100));
    }

    public function store(StorePaymentMethodRequest $request): PaymentMethodResource
    {
        Gate::authorize('create', PaymentMethod::class);
        $data = $request->validated();
        $method = PaymentMethod::create([
            'code' => 'PM-'.Str::upper(Str::random(12)),
            'name' => $data['name'],
            'type' => $data['type'],
            'provider' => $data['provider'],
            'fee_info' => $data['fee'] ?? 'Sesuai kontrak',
            'is_active' => $data['isActive'] ?? true,
            'created_by' => $request->user()->id,
        ]);
        $method->update(['code' => 'PM-'.str_pad((string) $method->id, 4, '0', STR_PAD_LEFT)]);

        AuditLog::record($request, 'payment_method.created', 'PaymentMethod', (string) $method->id, null, $this->auditState($method), $data['reason'] ?? 'Metode pembayaran dibuat');

        return PaymentMethodResource::make($method);
    }

    public function update(UpdatePaymentMethodRequest $request, string $code): PaymentMethodResource
    {
        $method = $this->findMethod($code);
        Gate::authorize('update', $method);
        $data = $request->validated();
        $before = $this->auditState($method);
        $method->fill(array_filter([
            'name' => $data['name'] ?? null,
            'type' => $data['type'] ?? null,
            'provider' => $data['provider'] ?? null,
            'fee_info' => $data['fee'] ?? null,
            'is_active' => $data['isActive'] ?? null,
        ], fn ($value): bool => $value !== null));
        $method->save();

        AuditLog::record($request, 'payment_method.updated', 'PaymentMethod', (string) $method->id, $before, $this->auditState($method), $data['reason'] ?? 'Metode pembayaran diperbarui');

        return PaymentMethodResource::make($method);
    }

    public function destroy(Request $request, string $code)
    {
        $method = $this->findMethod($code);
        Gate::authorize('delete', $method);
        AuditLog::record($request, 'payment_method.deleted', 'PaymentMethod', (string) $method->id, $this->auditState($method), null, $request->string('reason')->toString() ?: 'Metode pembayaran dihapus');
        $method->delete();

        return response()->noContent();
    }

    private function findMethod(string $code): PaymentMethod
    {
        return PaymentMethod::query()->where('code', $code)->firstOrFail();
    }

    private function auditState(PaymentMethod $method): array
    {
        return [
            'name' => $method->name,
            'type' => $method->type,
            'provider' => $method->provider,
            'fee' => $method->fee_info,
            'isActive' => $method->is_active,
            'usage' => $method->usage_count,
        ];
    }
}
