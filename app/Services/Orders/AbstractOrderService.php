<?php

namespace App\Services\Orders;

use App\Contracts\OrderService;
use App\Contracts\CatalogService;
use App\Contracts\PricingService;
use App\Enums\OrderStatus;
use App\Events\OrderPaid;
use App\Exceptions\DomainRuleViolation;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class AbstractOrderService implements OrderService
{
    public function __construct(protected readonly CatalogService $catalog, protected readonly PricingService $pricing) {}

    final public function create(User $user,array $items,?string $idempotencyKey=null): Order
    {
        if($items===[]) throw new DomainRuleViolation('Order must contain at least one item.','order.empty');
        if($idempotencyKey && ($existing=Order::query()->where('idempotency_key',$idempotencyKey)->first())) return $existing;
        return DB::transaction(function() use($user,$items,$idempotencyKey){
            $order=Order::query()->create(['uuid'=>Str::uuid()->toString(),'user_id'=>$user->id,'status'=>OrderStatus::PENDING,'currency'=>'IRR','idempotency_key'=>$idempotencyKey]);
            foreach($items as $item){
                $plan=$this->catalog->findPlan((int)($item['plan_id']??0)); $quantity=(int)($item['quantity']??1);
                if($quantity<=0) throw new DomainRuleViolation('Item quantity must be positive.','order.quantity');
                if($plan->status!=='active') throw new DomainRuleViolation('Cannot order an inactive plan.','order.plan_inactive');
                $unit=array_key_exists('unit_price',$item)?(int)$item['unit_price']:$this->pricing->price($plan,$order->currency);
                if($unit<0) throw new DomainRuleViolation('Unit price cannot be negative.','order.price');
                OrderItem::query()->create(['order_id'=>$order->id,'product_id'=>$plan->product_id,'plan_id'=>$plan->id,'name'=>$plan->name,'quantity'=>$quantity,'unit_price'=>$unit,'discount_amount'=>0,'total_amount'=>$unit*$quantity]);
            }
            return $this->recalculate($order);
        });
    }

    final public function recalculate(Order $order): Order
    {
        $subtotal=(int)$order->items()->sum('total_amount'); $discount=(int)$order->discount_amount; $wallet=(int)$order->wallet_amount;
        $order->forceFill(['subtotal'=>$subtotal,'total_amount'=>max(0,$subtotal-$discount-$wallet)])->save(); return $order->refresh();
    }

    final public function markPaid(Order $order): Order
    {
        if(in_array($order->status,[OrderStatus::CANCELLED,OrderStatus::FAILED],true)) throw new DomainRuleViolation('Cannot pay a cancelled or failed order.','order.invalid_payment_state');
        if(in_array($order->status,[OrderStatus::COMPLETED,OrderStatus::PAID],true)) return $order;
        DB::transaction(function() use($order){
            $order->refresh()->forceFill(['status'=>OrderStatus::PAID,'paid_at'=>now()])->save();
            if(!$order->invoice) Invoice::query()->create(['order_id'=>$order->id,'invoice_number'=>'INV-'.strtoupper(Str::random(12)),'status'=>'paid','subtotal'=>$order->subtotal,'discount_amount'=>$order->discount_amount,'total_amount'=>$order->total_amount,'currency'=>$order->currency,'issued_at'=>now(),'paid_at'=>now()]);
        });
        $order=$order->refresh(); OrderPaid::dispatch($order); return $order;
    }

    final public function cancel(Order $order): Order
    {
        if(in_array($order->status,[OrderStatus::COMPLETED,OrderStatus::PAID],true)) throw new DomainRuleViolation('Paid or completed orders require a refund flow instead of cancellation.','order.cancel_paid');
        $order->forceFill(['status'=>OrderStatus::CANCELLED,'cancelled_at'=>now()])->save(); return $order->refresh();
    }
}
