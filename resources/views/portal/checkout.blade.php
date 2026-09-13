@extends('layouts.portal')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div>
        <a href="{{ route('portal.products.show', $product) }}" class="text-sm text-ink-soft">← بازگشت به محصول</a>
        <h2 class="mt-3 text-2xl font-bold text-ink">تکمیل خرید</h2>
        <p class="mt-1 text-sm text-ink-soft">جزئیات سفارش را بررسی کنید و روش پرداخت را انتخاب کنید.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_.8fr]">
        <section class="glass-card p-6">
            <div class="flex items-center gap-4 border-b border-line pb-5">
                <div class="grid h-16 w-16 place-items-center rounded-2xl bg-ink text-xl font-bold text-white">{{ mb_substr($product->name ?? 'H', 0, 1) }}</div>
                <div>
                    <h3 class="font-bold text-ink">{{ $product->name }}</h3>
                    <p class="mt-1 text-sm text-ink-soft">{{ $product->description ?? 'محصول انتخاب‌شده' }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-5">
                <div>
                    <label class="mb-2 block text-sm font-semibold">پلن</label>
                    <select class="w-full rounded-xl border border-line bg-white px-4 py-3 text-sm">
                        @forelse($product->plans as $plan)
                            <option>{{ $plan->name }}</option>
                        @empty
                            <option>پلن فعالی وجود ندارد</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">کد تخفیف</label>
                    <div class="flex gap-2">
                        <input type="text" class="min-w-0 flex-1 rounded-xl border border-line px-4 py-3 text-sm" placeholder="کد تخفیف را وارد کنید">
                        <button type="button" class="rounded-xl bg-ink px-5 py-3 text-sm font-semibold text-white">اعمال</button>
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold">روش پرداخت</label>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <label class="cursor-pointer rounded-2xl border border-line p-4"><input type="radio" name="payment" checked> <span class="ms-2 text-sm">کیف پول</span></label>
                        <label class="cursor-pointer rounded-2xl border border-line p-4"><input type="radio" name="payment"> <span class="ms-2 text-sm">پرداخت آنلاین</span></label>
                        <label class="cursor-pointer rounded-2xl border border-line p-4"><input type="radio" name="payment"> <span class="ms-2 text-sm">پرداخت دستی</span></label>
                    </div>
                </div>
            </div>
        </section>

        <aside class="glass-card h-fit p-6">
            <h3 class="font-bold">خلاصه سفارش</h3>
            <div class="mt-5 space-y-4 text-sm">
                <div class="flex justify-between"><span class="text-ink-soft">محصول</span><span>{{ $product->name }}</span></div>
                <div class="flex justify-between"><span class="text-ink-soft">تخفیف</span><span>۰ تومان</span></div>
                <div class="border-t border-line pt-4 flex justify-between text-base font-bold"><span>مبلغ قابل پرداخت</span><span>بر اساس پلن انتخابی</span></div>
            </div>
            <button type="button" class="mt-6 w-full rounded-xl bg-brand px-5 py-3 font-bold text-white">ادامه و پرداخت</button>
            <p class="mt-3 text-center text-xs leading-5 text-ink-soft">با ادامه خرید، قوانین و شرایط استفاده از سرویس را می‌پذیرید.</p>
        </aside>
    </div>
</div>
@endsection
