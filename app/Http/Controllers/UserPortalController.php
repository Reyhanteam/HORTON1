<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Referral;
use App\Models\ReferralAccount;
use App\Models\Service;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;

final class UserPortalController extends Controller
{
    public function __construct(private readonly Request $request) {}

    private function user(): User
    {
        /** @var User $user */
        $user = $this->request->user();
        return $user;
    }

    public function dashboard()
    {
        $user = $this->user();
        $wallet = Wallet::query()->where('user_id', $user->id)->where('currency', 'IRR')->first();
        $services = Service::query()->with(['plan.product'])->where('user_id', $user->id)->latest()->limit(4)->get();
        $orders = Order::query()->with('items')->where('user_id', $user->id)->latest()->limit(5)->get();
        $notifications = Notification::query()->where('user_id', $user->id)->latest()->limit(5)->get();

        return view('portal.dashboard', compact('user', 'wallet', 'services', 'orders', 'notifications'));
    }

    public function shop(?Category $category = null)
    {
        $categories = Category::query()->active()->withCount(['products' => fn ($query) => $query->active()])->orderBy('sort_order')->get();
        $products = Product::query()->active()->with(['category', 'plans' => fn ($query) => $query->active()->orderBy('sort_order')])->when($category, fn ($query) => $query->where('category_id', $category->id))->orderBy('sort_order')->get();

        return view('portal.shop', compact('categories', 'products', 'category'));
    }

    public function product(Product $product)
    {
        abort_unless($product->status === 'active', 404);
        $product->load(['category', 'plans' => fn ($query) => $query->active()->orderBy('sort_order'), 'plans.prices']);

        return view('portal.product', compact('product'));
    }

    public function checkout(Product $product)
    {
        abort_unless($product->status === 'active', 404);
        $product->load(['category', 'plans' => fn ($query) => $query->active()->orderBy('sort_order'), 'plans.prices']);

        return view('portal.checkout', compact('product'));
    }

    public function orders()
    {
        $orders = Order::query()->with(['items', 'invoice', 'payments'])->where('user_id', $this->user()->id)->latest()->paginate(15);
        return view('portal.orders', compact('orders'));
    }

    public function order(Order $order)
    {
        abort_unless($order->user_id === $this->user()->id, 404);
        $order->load(['items.product', 'items.plan', 'invoice', 'payments', 'services']);
        return view('portal.order', compact('order'));
    }

    public function services()
    {
        $services = Service::query()->with(['plan.product', 'provider'])->where('user_id', $this->user()->id)->latest()->paginate(12);
        return view('portal.services', compact('services'));
    }

    public function service(Service $service)
    {
        abort_unless($service->user_id === $this->user()->id, 404);
        $service->load(['plan.product', 'provider', 'operations' => fn ($query) => $query->latest()->limit(10)]);
        return view('portal.service', compact('service'));
    }

    public function wallet()
    {
        $wallet = Wallet::query()->firstOrCreate(['user_id' => $this->user()->id, 'currency' => 'IRR'], ['balance' => 0, 'status' => 'active']);
        $transactions = $wallet->transactions()->latest()->paginate(15);
        return view('portal.wallet', compact('wallet', 'transactions'));
    }

    public function topUp()
    {
        return view('portal.wallet-topup');
    }

    public function transactions()
    {
        $wallet = Wallet::query()->firstOrCreate(['user_id' => $this->user()->id, 'currency' => 'IRR'], ['balance' => 0, 'status' => 'active']);
        $transactions = $wallet->transactions()->latest()->paginate(25);
        return view('portal.transactions', compact('wallet', 'transactions'));
    }

    public function referrals()
    {
        $account = ReferralAccount::query()->firstOrCreate(['user_id' => $this->user()->id], ['code' => strtoupper('H' . substr(bin2hex(random_bytes(5)), 0, 8))]);
        $referrals = Referral::query()->where('referrer_user_id', $this->user()->id)->latest()->paginate(15);
        return view('portal.referrals', compact('account', 'referrals'));
    }

    public function notifications()
    {
        $notifications = Notification::query()->where('user_id', $this->user()->id)->latest()->paginate(20);
        return view('portal.alerts', compact('notifications'));
    }

    public function support()
    {
        $tickets = SupportTicket::query()->withCount('messages')->where('user_id', $this->user()->id)->latest('last_message_at')->latest()->paginate(15);
        return view('portal.support', compact('tickets'));
    }

    public function createTicket()
    {
        return view('portal.support-create');
    }

    public function ticket(SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === $this->user()->id, 404);
        $ticket->load(['messages' => fn ($query) => $query->oldest()]);
        return view('portal.ticket', compact('ticket'));
    }

    public function faq()
    {
        return view('portal.faq');
    }

    public function tutorials()
    {
        return view('portal.tutorials');
    }

    public function discounts()
    {
        return view('portal.discounts');
    }

    public function profile()
    {
        return view('portal.profile', ['user' => $this->user(), 'profile' => $this->user()->profile]);
    }
}
