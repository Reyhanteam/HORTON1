<?php

namespace App\Providers;

use App\Contracts\AdminAuthenticator;
use App\Contracts\BotMessageStore;
use App\Contracts\CashbackService;
use App\Contracts\CatalogService;
use App\Contracts\ChannelMembershipService;
use App\Contracts\CheckoutService;
use App\Contracts\DiscountService;
use App\Contracts\FeatureManager;
use App\Contracts\GiftCodeService;
use App\Contracts\InvoiceService;
use App\Contracts\NotificationService;
use App\Contracts\OrderService;
use App\Contracts\OrderStateMachine;
use App\Contracts\PaymentGatewayContract;
use App\Contracts\PricingService;
use App\Contracts\ReferralService;
use App\Contracts\ServiceLifecycle;
use App\Contracts\ServiceProviderContract;
use App\Contracts\SettingsStore;
use App\Contracts\SupportService;
use App\Contracts\UserAccessChecker;
use App\Contracts\UserLifecycle;
use App\Contracts\WalletLedger;
use App\Contracts\WalletService;
use App\Services\Auth\LaravelAdminAuthenticator;
use App\Services\Auth\UserAccessService;
use App\Services\Auth\UserLifecycleService;
use App\Services\Catalog\DatabaseCatalogService;
use App\Services\Catalog\DatabasePricingService;
use App\Services\Invoices\DatabaseInvoiceService;
use App\Services\Marketing\DatabaseCashbackService;
use App\Services\Marketing\DatabaseDiscountService;
use App\Services\Marketing\DatabaseGiftCodeService;
use App\Services\Marketing\DatabaseReferralService;
use App\Services\Notifications\DatabaseNotificationService;
use App\Services\Orders\DatabaseCheckoutService;
use App\Services\Orders\DatabaseOrderService;
use App\Services\Orders\DatabaseOrderStateMachine;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Service\DatabaseServiceLifecycle;
use App\Services\ServiceProvider\FakeServiceProvider;
use App\Services\Settings\DatabaseSettingsStore;
use App\Services\Settings\FeatureManager as DatabaseFeatureManager;
use App\Services\Support\DatabaseSupportService;
use App\Services\Telegram\DatabaseBotMessageStore;
use App\Services\Telegram\DatabaseChannelMembershipService;
use App\Services\Wallet\DatabaseWalletLedger;
use App\Services\Wallet\DatabaseWalletService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsStore::class, DatabaseSettingsStore::class);
        $this->app->singleton(BotMessageStore::class, DatabaseBotMessageStore::class);
        $this->app->singleton(FeatureManager::class, DatabaseFeatureManager::class);
        $this->app->singleton(ChannelMembershipService::class, DatabaseChannelMembershipService::class);
        $this->app->singleton(AdminAuthenticator::class, LaravelAdminAuthenticator::class);
        $this->app->singleton(UserLifecycle::class, UserLifecycleService::class);
        $this->app->singleton(UserAccessChecker::class, UserAccessService::class);
        $this->app->singleton(CatalogService::class, DatabaseCatalogService::class);
        $this->app->singleton(PricingService::class, DatabasePricingService::class);
        $this->app->singleton(OrderStateMachine::class, DatabaseOrderStateMachine::class);
        $this->app->singleton(InvoiceService::class, DatabaseInvoiceService::class);
        $this->app->singleton(OrderService::class, DatabaseOrderService::class);
        $this->app->singleton(CheckoutService::class, DatabaseCheckoutService::class);
        $this->app->singleton(WalletService::class, DatabaseWalletService::class);
        $this->app->singleton(WalletLedger::class, DatabaseWalletLedger::class);
        $this->app->singleton(DiscountService::class, DatabaseDiscountService::class);
        $this->app->singleton(GiftCodeService::class, DatabaseGiftCodeService::class);
        $this->app->singleton(ReferralService::class, DatabaseReferralService::class);
        $this->app->singleton(CashbackService::class, DatabaseCashbackService::class);
        $this->app->singleton(ServiceLifecycle::class, DatabaseServiceLifecycle::class);
        $this->app->singleton(ServiceProviderContract::class, FakeServiceProvider::class);
        $this->app->singleton(NotificationService::class, DatabaseNotificationService::class);
        $this->app->singleton(SupportService::class, DatabaseSupportService::class);
        $this->app->singleton(PaymentGatewayContract::class, FakePaymentGateway::class);
    }
}
