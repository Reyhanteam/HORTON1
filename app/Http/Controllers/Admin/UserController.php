<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\TelegramAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class UserController
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('telegramAccounts')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim((string) $request->input('q'));
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhereHas('telegramAccounts', function ($telegram) use ($term): void {
                            $telegram->where('username', 'like', "%{$term}%")
                                ->orWhere('telegram_user_id', 'like', "%{$term}%");
                        });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest('id')
            ->paginate(min((int) $request->input('per_page', 25), 100));

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->load(['telegramAccounts', 'profile']),
            'wallets' => Wallet::query()->where('user_id', $user->id)->get(),
            'orders' => Order::query()->where('user_id', $user->id)->latest('id')->limit(20)->get(),
            'payments' => Payment::query()->where('user_id', $user->id)->latest('id')->limit(20)->get(),
            'services' => Service::query()->where('user_id', $user->id)->latest('id')->limit(20)->get(),
            'support_tickets' => SupportTicket::query()->where('user_id', $user->id)->latest('id')->limit(20)->get(),
            'wallet_transactions' => WalletTransaction::query()->where('user_id', $user->id)->latest('id')->limit(50)->get(),
        ]);
    }

    public function status(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
        ]);

        $old = $user->status;
        $user->update(['status' => $data['status']]);

        $this->audit('user.status_changed', $user, ['status' => $old], ['status' => $user->status]);

        return response()->json($user->refresh());
    }

    public function telegram(User $user): JsonResponse
    {
        return response()->json(
            TelegramAccount::query()->where('user_id', $user->id)->latest('id')->get()
        );
    }

    private function audit(string $action, User $user, array $oldValues, array $newValues): void
    {
        AuditLog::query()->create([
            'admin_user_id' => AdminUser::query()->where('email', auth()->user()->email)->value('id'),
            'action' => $action,
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
