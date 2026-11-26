@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

    // Stock status -> badge class (see app.css)
    $stockBadge = [
        'Out of stock'     => 'badge-low',
        'Low stock'        => 'badge-low',
        'Restock showroom' => 'badge-restock',
        'OK'               => 'badge-ok',
    ];
@endphp

{{-- Page header --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-sub">{{ $greeting }}, {{ $firstName }}. Here is your stock and order summary for {{ now()->format('M j, Y') }}.</p>
    </div>

    @if ($role === 'kasikas')
        <a href="{{ route('orders') }}" class="btn">+ New Order</a>
    @endif
</div>

{{-- Order cards --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    @foreach ($stats as [$label, $value, $unit])
        <a href="{{ route('orders') }}" class="card-link">
            <div class="stat-label">{{ $label }}</div>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-4xl font-extrabold">{{ $value }}</span>
                <span class="text-sm text-muted">{{ $unit }}</span>
            </div>
        </a>
    @endforeach
</div>

{{-- Stock table + low stock --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    <div class="card lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-bold text-lg">Stock by Location</h2>
            <a href="{{ route('availability') }}" class="link-muted">View details</a>
        </div>

        @php
            $statusCounts = collect($stocks)->countBy(fn ($s) => $s[3]);
            $chip = fn ($active) => 'rounded-full border px-3 py-1 text-xs font-semibold '
                . ($active ? 'border-brand bg-brand text-white' : 'border-stone-300 text-muted hover:bg-cream');
        @endphp
        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('overview') }}" class="{{ $chip(! request('stock')) }}">All {{ count($stocks) }}</a>
            @foreach (['Out of stock', 'Low stock', 'Restock showroom', 'OK'] as $s)
                <a href="{{ route('overview', ['stock' => $s]) }}"
                   class="{{ $chip(request('stock') === $s) }}">{{ $s }} {{ $statusCounts[$s] ?? 0 }}</a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="table-head">
                        <th class="th">Furniture</th>
                        <th class="th">Warehouse</th>
                        <th class="th">Showroom</th>
                        <th class="th">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stocks as [$name, $wh, $sr, $status])
                        @continue(request('stock') && request('stock') !== $status)
                        <tr class="table-row">
                            <td class="td">
                                <div class="flex items-center gap-3">
                                    <x-furniture-image :name="$name" size="h-9 w-9" />
                                    <span class="font-medium">{{ $name }}</span>
                                </div>
                            </td>
                            <td class="td-muted">{{ $wh }}</td>
                            <td class="td-muted">{{ $sr }}</td>
                            <td class="td"><span class="badge {{ $stockBadge[$status] }}">{{ $status }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card self-start">
        <h2 class="font-bold text-lg">Low Stock</h2>
        <p class="text-xs text-muted mb-4">Below the minimum level of 5 units</p>
        @forelse ($critical as [$name, $sku, $total])
            <div class="mb-2 flex items-center justify-between gap-3 rounded-lg bg-red-50 px-4 py-3">
                <div class="flex items-center gap-3">
                    <x-furniture-image :name="$name" size="h-9 w-9" />
                    <div>
                        <div class="text-sm font-semibold">{{ $name }}</div>
                        <div class="text-xs text-muted">{{ $sku }}</div>
                    </div>
                </div>
                <div class="text-sm font-semibold text-red-700 whitespace-nowrap">{{ $total }} left</div>
            </div>
        @empty
            <p class="text-sm text-muted">Nothing is below the minimum level.</p>
        @endforelse
    </div>
</div>

{{-- Most and least ordered --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="card">
        <h2 class="font-bold text-lg">Most Ordered</h2>
        <p class="text-xs text-muted mb-4">Units delivered so far</p>
        @foreach ($topSelling as $i => [$name, $qty])
            <div class="flex items-center gap-3 border-t border-stone-200 py-3 text-sm">
                <span class="w-5 font-bold text-muted">{{ $i + 1 }}</span>
                <x-furniture-image :name="$name" size="h-9 w-9" />
                <span class="flex-1 font-medium">{{ $name }}</span>
                <span class="badge badge-ok">{{ $qty }} units</span>
            </div>
        @endforeach
    </div>

    <div class="card">
        <h2 class="font-bold text-lg">Least Ordered</h2>
        <p class="text-xs text-muted mb-4">Slow-moving furniture</p>
        @foreach ($leastSelling as $i => [$name, $qty])
            <div class="flex items-center gap-3 border-t border-stone-200 py-3 text-sm">
                <span class="w-5 font-bold text-muted">{{ $i + 1 }}</span>
                <x-furniture-image :name="$name" size="h-9 w-9" />
                <span class="flex-1 font-medium">{{ $name }}</span>
                <span class="badge badge-restock">{{ $qty }} units</span>
            </div>
        @endforeach
    </div>
</div>

{{-- Latest orders --}}
<div class="card">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="font-bold text-lg">Recent Orders</h2>
        <a href="{{ route('orders') }}" class="link-muted">View all orders</a>
    </div>
    @forelse ($recent as [$no, $text, $status])
        <div class="flex items-center gap-4 border-t border-stone-200 py-3 text-sm">
            <span class="w-20 font-mono text-xs font-bold">{{ $no }}</span>
            <span class="flex-1 font-medium">{{ $text }}</span>
            <span class="badge badge-{{ str_replace(' ', '-', strtolower($status)) }}">{{ $status }}</span>
        </div>
    @empty
        <p class="text-sm text-muted">No orders yet.</p>
    @endforelse
</div>
@endsection