<?php

namespace App\Contracts;

use App\Models\Service;
use App\Models\User;

interface ServiceLifecycle
{
    public function create(User $user, int $planId, int $providerId, array $attributes = []): Service;
    public function renew(Service $service, int $durationValue, string $durationUnit = 'day'): Service;
    public function extendCapacity(Service $service, int $amount): Service;
    public function disable(Service $service): Service;
}
