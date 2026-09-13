<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Wallet\CreditWalletAction;
use App\Actions\Wallet\DebitWalletAction;
use App\DTOs\WalletMutationData;
use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class WalletController
{
    public function show(User $user, string $currency = 'IRR'): JsonResponse
    {
        $wallet = Wallet::query()->where('user_id', $user->id)->where('currency', strtoupper($currency))->firstOrCreate([
            'user_id' => $user->id,
            'currency' => strtoupper($currency),
        ], [
            'balance' => 0,
            'status' => 'active',
        ]);

        return response()->json([
            'wallet' => $wallet,
            'transactions' => $wallet->transactions()->latest('id')->paginate(50),
        ]);
    }

    public function transactions(Request $request, User $user): JsonResponse
    {
        $query = WalletTransaction::query()->where('user_id', $user->id)->latest('id');

        if ($request->filled('direction')) {
            $query->where('direction', $request->string('direction')->toString());
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 50), 100)));
    }

    public function credit(Request $request, User $user, CreditWalletAction $action): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'regex:/^[A-Za-z]{3}$/'],
            'description' => ['required', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
        ]);

        $transaction = $action->execute($user, new WalletMutationData(
            amount: (int) $data['amount'],
            type: 'admin_credit',
            description: $data['description'],
            idempotencyKey: $data['idempotency_key'] ?? null,
            metadata: ['admin_user_id' => $this->adminUserId()],
        ), $data['currency'] ?? 'IRR');

        $this->audit('wallet.credit', $user, $transaction->id, ['amount' => $transaction->amount]);

        return response()->json($transaction->load('wallet'), 201);
    }

    public function debit(Request $request, User $user, DebitWalletAction $action): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'regex:/^[A-Za-z]{3}$/'],
            'description' => ['required', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
        ]);

        $transaction = $action->execute($user, new WalletMutationData(
            amount: (int) $data['amount'],
            type: 'admin_debit',
            description: $data['description'],
            idempotencyKey: $data['idempotency_key'] ?? null,
            metadata: ['admin_user_id' => $this->adminUserId()],
        ), $data['currency'] ?? 'IRR');

        $this->audit('wallet.debit', $user, $transaction->id, ['amount' => $transaction->amount]);

        return response()->json($transaction->load('wallet'), 201);
    }

    public function status(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'currency' => ['nullable', 'regex:/^[A-Za-z]{3}$/'],
        ]);

        $wallet = Wallet::query()->where('user_id', $user->id)->where('currency', strtoupper($data['currency'] ?? 'IRR'))->firstOrFail();
        $old = $wallet->status;
        $wallet->update(['status' => $data['status']]);

        $this->audit('wallet.status_changed', $user, $wallet->id, ['from' => $old, 'to' => $wallet->status]);

        return response()->json($wallet->refresh());
    }

    private function adminUserId(): ?int
    {
        $email = auth()->user()?->email;

        return $email
            ? AdminUser::query()->where('email', $email)->value('id')
            : null;
    }

    private function audit(string $action, User $user, int $referenceId, array $newValues): void
    {
        AuditLog::query()->create([
            'admin_user_id' => $this->adminUserId(),
            'action' => $action,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'old_values' => [],
            'new_values' => [...$newValues, 'reference_id' => $referenceId],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
