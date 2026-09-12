<?php

namespace App\Enums;

enum ServiceProviderOperation: string
{
    case Create = 'create';
    case Get = 'get';
    case Renew = 'renew';
    case Extend = 'extend';
    case AddCapacity = 'add_capacity';
    case Disable = 'disable';
    case Delete = 'delete';
    case Status = 'status';
}
