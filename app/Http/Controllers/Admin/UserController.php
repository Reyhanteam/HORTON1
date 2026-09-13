<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\AdminUser;
use App\Models\AuditLog;
use App\Models\CashbackAccount;
use App\Models\CashbackTransaction;
use App\Models\DiscountUsage;
use App\Models\GiftCodeRedemption;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->paginate(min(max((int) $request->input('per_page', 25), 1), 100));

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
            'referrals' => Referral::query()->where(function ($query) use ($user): void {
                $query->where('referrer_user_id', $user->id)->orWhere('referred_user_id', $user->id);
            })->latest('id')->limit(50)->get(),
            'cashback' => CashbackAccount::query()->where('user_id', $user->id)->get(),
            'discount_usages' => DiscountUsage::query()->where('user_id', $user->id)->with('discountCode')->latest('id')->limit(50)->get(),
            'gift_redemptions' => GiftCodeRedemption::query()->where('user_id', $user->id)->with('giftCode')->latest('id')->limit(50)->get(),
            'notifications' => $user->notifications()->latest()->limit(50)->get(),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $oldValues = $user->only(array_keys($data));
        $user->update($data);

        $this->audit('user.updated', $user, $oldValues, $user->only(array_keys($data)));

        return response()->json($user->refresh()->load('profile'));
    }

    public function activate(User $user): JsonResponse
    {
        return $this->changeStatus($user, 'active');
    }

    public function deactivate(User $user): JsonResponse
    {
        return $this->changeStatus($user, 'inactive');
    }

    public function block(User $user): JsonResponse
    {
        return $this->changeStatus($user, 'blocked');
    }

    public function unblock(User $user): JsonResponse
    {
        return $this->changeStatus($user, 'active');
    }

    public function status(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'blocked'])],
        ]);

        return $this->changeStatus($user, $data['status']);
    }

    public function wallet(User $user): JsonResponse
    {
        return response()->json(
            Wallet::query()->where('user_id', $user->id)->withCount('transactions')->get()
        );
    }

    public function orders(Request $request, User $user): JsonResponse
    {
        return response()->json(
            Order::query()->where('user_id', $user->id)->latest('id')->paginate($this->perPage($request))
        );
    }

    public function payments(Request $request, User $user): JsonResponse
    {
        return response()->json(
            Payment::query()->where('user_id', $user->id)->latest('id')->paginate($this->perPage($request))
        );
    }

    public function services(Request $request, User $user): JsonResponse
    {
        return response()->json(
            Service::query()->where('user_id', $user->id)->latest('id')->paginate($this->perPage($request))
        );
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

        return response()->json($query->paginate($this->perPage($request, 50)));
    }

    public function referrals(Request $request, User $user): JsonResponse
    {
        $query = Referral::query()
            ->where(function ($q) use ($user): void {
                $q->where('referrer_user_id', $user->id)->orWhere('referred_user_id', $user->id);
            })
            ->with(['referrer:id,name,email', 'referred:id,name,email'])
            ->latest('id');

        return response()->json($query->paginate($this->perPage($request)));
    }

    public function cashback(Request $request, User $user): JsonResponse
    {
        $account = CashbackAccount::query()->where('user_id', $user->id)->first();
        $transactions = CashbackTransaction::query()->where('user_id', $user->id)->latest('id')->paginate($this->perPage($request, 50));

        return response()->json([
            'account' => $account,
            'transactions' => $transactions,
        ]);
    }

    public function discounts(Request $request, User $user): JsonResponse
    {
        return response()->json(
            DiscountUsage::query()
                ->where('user_id', $user->id)
                ->with('discountCode')
                ->latest('id')
                ->paginate($this->perPage($request))
        );
    }

    public function gifts(Request $request, User $user): JsonResponse
    {
        return response()->json(
            GiftCodeRedemption::query()
                ->where('user_id', $user->id)
                ->with('giftCode')
                ->latest('id')
                ->paginate($this->perPage($request))
        );
    }

    public function support(Request $request, User $user): JsonResponse
    {
        return response()->json(
            SupportTicket::query()->where('user_id', $user->id)->latest('id')->paginate($this->perPage($request))
        );
    }

    public function notifications(Request $request, User $user): JsonResponse
    {
        return response()->json(
            $user->notifications()->latest()->paginate($this->perPage($request, 50))
        );
    }

    public function activity(Request $request, User $user): JsonResponse
    {
        $query = AuditLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->latest('id');

        return response()->json($query->paginate($this->perPage($request, 50)));
    }

    public function note(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $audit = AuditLog::query()->create([
            'admin_user_id' => $this->adminUserId(),
            'action' => 'user.internal_note_added',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'old_values' => [],
            'new_values' => ['note' => $data['note']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json($audit, 201);
    }

    private function changeStatus(User $user, string $status): JsonResponse
    {
        $oldStatus = $user->status;

        if ($oldStatus !== $status) {
            $user->update(['status' => $status]);
            $this->audit('user.status_changed', $user, ['status' => $oldStatus], ['status' => $status]);
        }

        return response()->json($user->refresh());
    }

    private function perPage(Request $request, int $default = 25): int
    {
        return min(max((int) $request->input('per_page', $default), 1), 100);
    }

    private function adminUserId(): ?int
    {
        $email = auth()->user()?->email;

        return $email
            ? AdminUser::query()->where('email', $email)->value('id')
            : null;
    }

    private function audit(string $action, User $user, array $oldValues, array $newValues): void
    {
        AuditLog::query()->create([
            'admin_user_id' => $this->adminUserId(),
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
