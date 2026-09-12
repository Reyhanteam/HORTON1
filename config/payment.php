<?php

return [
    'default_gateway' => env('PAYMENT_GATEWAY', 'fake'),
    'gateways' => [
        'fake' => App\Services\Payment\FakePaymentGateway::class,
        'manual' => App\Services\Payment\ManualPaymentGateway::class,
    ],
];
