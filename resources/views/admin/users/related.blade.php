@extends('layouts.dashboard', ['title' => 'HORTON | '.$title, 'heading' => $title])

@section('content')
<div class="mx-auto max-w-[1500px] space-y-5">
    <div class="flex items-center justify-between gap-4">
        <div><p class="text-sm text-ink-soft">کاربر: {{ $user->name }}</p><h1 class="mt-1 text-3xl font-black text-ink">{{ $title }}</h1></div>
        <a href="{{ route('admin.users.show', $user) }}" class="rounded-xl border border-line bg-white px-4 py-3 text-sm font-bold">بازگشت به پروفایل</a>
    </div>
    <section class="overflow-hidden rounded-2xl border border-line bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-right text-sm">
                <thead class="bg-slate-50 text-xs text-ink-soft"><tr>@foreach($columns as $label)<th class="px-5 py-4 font-bold">{{ $label }}</th>@endforeach</tr></thead>
                <tbody class="divide-y divide-line">
                @forelse($items as $item)
                    <tr class="hover:bg-slate-50/80">
                        @foreach($columns as $field => $label)
                            @php($value = data_get($item, $field))
                            <td class="px-5 py-4">{{ $value instanceof \Carbon\CarbonInterface ? $value->format('Y/m/d H:i') : (is_scalar($value) ? $value : '—') }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="px-5 py-14 text-center text-ink-soft">موردی برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($items, 'links'))<div class="border-t border-line px-5 py-4">{{ $items->links() }}</div>@endif
    </section>
</div>
@endsection
