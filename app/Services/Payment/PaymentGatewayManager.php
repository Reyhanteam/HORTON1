<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayContract;
use App\Contracts\SettingsStore;
use App\Exceptions\PaymentException;
use InvalidArgumentException;

final class PaymentGatewayManager
{
    public function __construct(private readonly SettingsStore $settings) {}

    public function driver(?string $name = null): PaymentGatewayContract
    {
        $name ??= (string) $this->settings->get('payment.default_gateway', config('payment.default_gateway', 'fake'));
        $class = config("payment.gateways.{$name}");
        if (! is_string($class) || ! class_exists($class)) {
            throw new PaymentException('Payment gateway is not configured.', 'payment.gateway_not_configured');
        }
        $gateway = app($class);
        if (! $gateway instanceof PaymentGatewayContract) {
            throw new InvalidArgumentException("Payment gateway [{$name}] must implement PaymentGatewayContract.");
        }
        return $gateway;
    }
}
