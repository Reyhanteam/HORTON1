<?php

namespace App\Services\Service;

use App\Contracts\ServiceLifecycle;
use App\Enums\ServiceStatus;
use App\Exceptions\DomainRuleViolation;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

abstract class AbstractServiceLifecycle implements ServiceLifecycle
{
    final public function create(User $user,int $planId,int $providerId,array $attributes=[]): Service
    {
        $plan=Plan::query()->findOrFail($planId);
        if($plan->status!=='active') throw new DomainRuleViolation('Plan is not active.','service.plan_inactive');
        return Service::query()->create(array_merge(['uuid'=>Str::uuid()->toString(),'user_id'=>$user->id,'plan_id'=>$plan->id,'service_provider_id'=>$providerId,'status'=>ServiceStatus::PENDING,'capacity'=>$plan->capacity_value,'used_capacity'=>0,'is_trial'=>(bool)$plan->is_trial,'starts_at'=>Carbon::now()],$attributes));
    }
    final public function renew(Service $service,int $durationValue,string $durationUnit='day'): Service
    {
        if($durationValue<=0) throw new DomainRuleViolation('Renewal duration must be positive.','service.duration');
        $base=$service->expires_at&&$service->expires_at->isFuture()?$service->expires_at->copy():Carbon::now();
        $service->expires_at=$base->add($durationValue.' '.$durationUnit); $service->status=ServiceStatus::ACTIVE; $service->save(); return $service->refresh();
    }
    final public function extendCapacity(Service $service,int $amount): Service
    {
        if($amount<=0) throw new DomainRuleViolation('Capacity extension must be positive.','service.capacity');
        $service->capacity=((int)$service->capacity)+$amount; $service->save(); return $service->refresh();
    }
    final public function disable(Service $service): Service
    {
        if($service->status===ServiceStatus::DISABLED) return $service;
        $service->status=ServiceStatus::DISABLED; $service->save(); return $service->refresh();
    }
}
