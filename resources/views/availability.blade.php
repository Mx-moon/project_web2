@extends('layouts.app')
@section('title', 'Availability')

@section('content')
@php
    if ($showAll) {
        // Add up every furniture, location by location
        $selected = 'all';
        $stock    = [];
        $lowCount = 0;

        foreach ($products as $row) {
            foreach ($row as $location => $qty) {
                $stock[$location] = ($stock[$location] ?? 0) + $qty;
            }

            if (array_sum($row) < $threshold) {
                $lowCount++;
            }
        }

        $total = array_sum($stock);
    } else {
        $selected = request('product');
        $stock    = $products[$selected];
        $total    = array_sum($stock);
        $isLow    = $total < $threshold;
    }
@endphp

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="page-title">Availability</h1>
        <p class="page-sub">Check how many units of each furniture are at each location</p>
    </div>

    @if ($canAdd)
        <button type="button" data-modal-open="stock-modal" class="btn">+ Add Stock</button>
    @endif
</div>

@if (session('status'))
    <div class="alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

{{-- Add stock pop-up --}}
@if ($canAdd)
    <dialog id="stock-modal"
            class="m-auto w-full max-w-md rounded-xl border border-stone-300 bg-white p-0 backdrop:bg-black/50"
            @if ($errors->any() && old('product_id')) data-open-on-load @endif>
        <form method="POST" action="{{ route('availability.stock') }}" class="p-6">
            @csrf

            <div class="mb-4 flex items-center justify-between">
                <h2 class="card-title mb-0">Add Stock</h2>
                <button type="button" data-modal-close class="text-2xl leading-none text-muted hover:text-ink" aria-label="Close">&times;</button>
            </div>

            <label class="label">Furniture</label>
            <select name="product_id" class="field">
                @foreach ($productList as $p)
                    <option value="{{ $p->id }}" @selected(old('product_id') == $p->id || (! $showAll && $p->name === $selected))>{{ $p->name }}</option>
                @endforeach
            </select>

            <label class="label">Add to warehouse</label>
            <select name="location_id" class="field">
                @foreach ($warehouses as $w)
                    <option value="{{ $w->id }}" @selected(old('location_id') == $w->id)>{{ $w->name }}</option>
                @endforeach
            </select>

            <label class="label">Quantity to add</label>
            <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required class="field">

            <div class="flex gap-2">
                <button type="submit" class="btn-block">Add Stock</button>
                <button type="button" data-modal-close class="btn-outline">Cancel</button>
            </div>
        </form>
    </dialog>
@endif

<div class="card">

    <form method="GET" class="mb-6">
        <label class="label">Select furniture</label>
        <select name="product" data-autosubmit
                class="min-w-64 rounded-lg border border-ink bg-white px-3 py-2.5 text-sm font-medium">
            <option value="all" @selected($showAll)>All furniture</option>
            @foreach (array_keys($products) as $name)
                <option value="{{ $name }}" @selected(! $showAll && $name === $selected)>{{ $name }}</option>
            @endforeach
        </select>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        @foreach ($stock as $location => $qty)
            <div class="rounded-xl bg-cream p-6 text-center">
                <div class="stat-label">{{ $location }}</div>
                <div class="mt-3 text-5xl font-extrabold">{{ $qty }}</div>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-stone-300 px-4 py-3 text-sm">
        <div class="flex items-center gap-3">
            <span class="text-muted">Stock status:</span>
            @if ($showAll)
                @if ($lowCount > 0)
                    <span class="badge badge-low">{{ $lowCount }} low on stock</span>
                @else
                    <span class="badge badge-ok">All stock OK</span>
                @endif
            @elseif ($isLow)
                <span class="badge badge-low">Low stock</span>
            @else
                <span class="badge badge-ok">Stock OK</span>
            @endif
        </div>
        <div class="text-muted">
            Total: <strong class="text-ink">{{ $total }} units</strong>.
            Low stock level: <strong class="text-ink">{{ $threshold }} units</strong>
        </div>
    </div>
</div>

{{-- All furniture: one row each --}}
@if ($showAll)
    <div class="card mt-6">
        <h2 class="card-title">All Furniture</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="table-head">
                        <th class="th">Furniture</th>
                        @foreach (array_keys($stock) as $location)
                            <th class="th">{{ $location }}</th>
                        @endforeach
                        <th class="th">Total</th>
                        <th class="py-2 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $name => $row)
                        @php $rowTotal = array_sum($row); @endphp
                        <tr class="table-row">
                            <td class="td">
                                <div class="flex items-center gap-3">
                                    <x-furniture-image :name="$name" size="h-9 w-9" />
                                    <span class="font-medium">{{ $name }}</span>
                                </div>
                            </td>
                            @foreach ($row as $qty)
                                <td class="td-muted">{{ $qty }}</td>
                            @endforeach
                            <td class="td font-bold">{{ $rowTotal }}</td>
                            <td class="py-3">
                                @if ($rowTotal < $threshold)
                                    <span class="badge badge-low">Low stock</span>
                                @else
                                    <span class="badge badge-ok">Stock OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($stock) + 3 }}" class="py-6 text-center text-muted">No furniture yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- Stock history --}}
<div class="card mt-6">
    <h2 class="card-title">Stock History</h2>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="table-head">
                    <th class="th">Date</th>
                    @if ($showAll)
                        <th class="th">Furniture</th>
                    @endif
                    <th class="th">Location</th>
                    <th class="th">Change</th>
                    <th class="th">Reason</th>
                    <th class="py-2 font-semibold">By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $move)
                    <tr class="table-row">
                        <td class="td-muted whitespace-nowrap">{{ $move->created_at->format('M j, Y g:i A') }}</td>
                        @if ($showAll)
                            <td class="td">{{ $move->product->name ?? '-' }}</td>
                        @endif
                        <td class="td">{{ $move->location->name }}</td>
                        <td class="td font-bold {{ $move->change < 0 ? 'text-red-700' : 'text-green-700' }}">{{ $move->change > 0 ? '+' : '' }}{{ $move->change }}</td>
                        <td class="td-muted">{{ ucfirst($move->reason) }}</td>
                        <td class="py-3 text-muted">{{ $move->user->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showAll ? 6 : 5 }}" class="py-6 text-center text-muted">No stock changes yet{{ $showAll ? '.' : ' for this furniture.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection