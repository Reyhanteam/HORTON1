<?php

namespace App\Actions\Services;

use App\Contracts\ServiceLifecycle;
use App\Models\Service;
use App\Models\User;

final class CreateServiceAction
{
    public function __construct(private readonly ServiceLifecycle $services) {}
    public function execute(User $user,int $planId,int $providerId,array $attributes=[]): Service { return $this->services->create($user,$planId,$providerId,$attributes); }
}
