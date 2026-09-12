<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case ONLINE = 'online';
    case MANUAL = 'manual';
    case WALLET = 'wallet';
}
