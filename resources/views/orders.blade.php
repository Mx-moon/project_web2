@extends('layouts.app')
@section('title', 'Orders')

@section('content')
@php
    $role = auth()->user()->role;
@endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="page-title">Orders</h1>

    @if ($role === 'kasikas')
        <button type="button" data-modal-open="order-modal" class="btn">+ New Order</button>
    @endif
</div>

@if (session('status'))
    <div class="alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

<div class="grid grid-cols-1 gap-4">

    {{-- Order form: Kasikas only --}}
    @if ($role === 'kasikas')
        <dialog id="order-modal"
                class="m-auto w-full max-w-md rounded-xl border border-stone-300 bg-white p-0 backdrop:bg-black/50"
                @if ($errors->any() && old('product_id')) data-open-on-load @endif>
            <form method="POST" action="{{ route('orders.store') }}" class="p-6">
                @csrf

                <div class="mb-4 flex items-center justify-between">
                    <h2 class="card-title mb-0">Request New Order</h2>
                    <button type="button" data-modal-close class="text-2xl leading-none text-muted hover:text-ink" aria-label="Close">&times;</button>
                </div>

                @if ($errors->any())
                    <div class="alert-error">{{ $errors->first() }}</div>
                @endif

                <label class="label">Select product</label>
                <select name="product_id" class="field">
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} ({{ $available[$product->id] ?? 0 }} available)</option>
                    @endforeach
                </select>

                <label class="label">Quantity</label>
                <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" class="field">

                <label class="label">Ship from</label>
                <select name="from_location_id" class="field">
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>

                <label class="label">Deliver to</label>
                <div class="field">{{ auth()->user()->location->name ?? 'No location assigned' }}</div>

                <div class="flex gap-2">
                    <button type="submit" class="btn-block">Submit Order</button>
                    <button type="button" data-modal-close class="btn-outline">Cancel</button>
                </div>
            </form>
        </dialog>
    @endif

    {{-- Orders log --}}
    <div class="card">
        <h2 class="card-title">Orders Log</h2>

                @php
            $tabs = ['Pending', 'Preparing', 'In Transit', 'Delivered', 'Rejected', 'Cancelled'];
            $tabClass = fn ($active) => 'rounded-full border px-3 py-1 text-xs font-semibold '
                . ($active ? 'border-brand bg-brand text-white' : 'border-stone-300 text-muted hover:bg-cream');
        @endphp

        <form method="GET" action="{{ route('orders') }}" class="mb-4">
            <div class="mb-3 flex flex-wrap gap-2">
                <a href="{{ route('orders', request()->except('status')) }}" class="{{ $tabClass(! request('status')) }}">All {{ $counts->sum() }}</a>
                @foreach ($tabs as $tab)
                    <a href="{{ route('orders', array_merge(request()->except('status'), ['status' => $tab])) }}"
                       class="{{ $tabClass(request('status') === $tab) }}">{{ $tab }} {{ $counts[$tab] ?? 0 }}</a>
                @endforeach
            </div>

            @if (request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif

            <div class="flex flex-wrap gap-2">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search order no. or product"
                       class="min-w-48 flex-1 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand">

                @if ($role !== 'kasikas')
                    <select name="showroom" data-autosubmit class="field-small py-2">
                        <option value="">All showrooms</option>
                        @foreach ($showrooms as $room)
                            <option value="{{ $room->id }}" @selected(request('showroom') == $room->id)>{{ $room->name }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="days" data-autosubmit class="field-small py-2">
                    <option value="">All time</option>
                    <option value="7"  @selected(request('days') == 7)>Last 7 days</option>
                    <option value="30" @selected(request('days') == 30)>Last 30 days</option>
                    <option value="90" @selected(request('days') == 90)>Last 90 days</option>
                </select>

                <button type="submit" class="btn-sm">Search</button>
                @if (request()->hasAny(['status', 'q', 'showroom', 'days']))
                    <a href="{{ route('orders') }}" class="btn-sm-outline">Clear</a>
                @endif
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="table-head">
                        <th class="th">Order No.</th>
                        <th class="th">Product</th>
                        <th class="th">Qty</th>
                        <th class="th">From</th>
                        <th class="th">Deliver To</th>
                        <th class="th">Date</th>
                        <th class="th">Status</th>
                        <th class="py-2 font-semibold text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="table-row align-top">
                            <td class="td font-mono text-xs font-bold whitespace-nowrap">{{ $order->order_no }}</td>
                            <td class="td">
                                <div class="flex items-center gap-3">
                                    <x-furniture-image :name="$order->product->name" :photo="$order->product->photo_url" size="h-9 w-9" />
                                    <span class="font-medium">{{ $order->product->name }}</span>
                                </div>
                            </td>
                            <td class="td-muted whitespace-nowrap">{{ $order->quantity }} {{ $order->quantity === 1 ? 'unit' : 'units' }}</td>
                            <td class="td-muted">{{ $order->fromLocation->name }}</td>
                            <td class="td-muted">{{ $order->toLocation->name }}</td>
                            <td class="td-muted whitespace-nowrap">{{ $order->created_at->format('M j, Y') }}</td>
                            <td class="td">
                                <span class="badge badge-{{ str_replace(' ', '-', strtolower($order->status)) }}">{{ $order->status }}</span>
                                @if ($order->reject_reason)
                                    <div class="note">{{ $order->reject_reason }}</div>
                                @endif
                                @if ($order->received_at)
                                    <div class="note">Received {{ $order->received_quantity }} of {{ $order->quantity }} ({{ $order->received_condition }}){{ $order->received_notes ? ': '.$order->received_notes : '' }}</div>
                                @endif
                            </td>
                            <td class="py-3">
                                <div class="flex flex-wrap items-center justify-end gap-2">

                                    @if ($role === 'finali' && $order->status === 'Pending')
                                        <form method="POST" action="{{ route('orders.approve', $order) }}">
                                            @csrf
                                            <button type="submit" class="btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('orders.reject', $order) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input type="text" name="reject_reason" placeholder="Reason" required class="field-small w-28">
                                            <button type="submit" class="btn-sm-outline">Reject</button>
                                        </form>

                                    @elseif ($role === 'finali' && $order->status === 'Preparing')
                                        <form method="POST" action="{{ route('orders.ship', $order) }}">
                                            @csrf
                                            <button type="submit" class="btn-sm">Dispatch</button>
                                        </form>

                                    @elseif ($role === 'kasikas' && $order->status === 'Pending')
                                        <form method="POST" action="{{ route('orders.cancel', $order) }}">
                                            @csrf
                                            <button type="submit" class="btn-sm-outline">Cancel</button>
                                        </form>

                                    @elseif ($role === 'kasikas' && $order->status === 'In Transit')
                                        <form method="POST" action="{{ route('orders.receive', $order) }}" class="flex items-center gap-1">
                                            @csrf
                                            <input type="number" name="received_quantity" min="0" max="{{ $order->quantity }}" value="{{ $order->quantity }}" class="field-small w-14">
                                            <select name="received_condition" class="field-small">
                                                <option>Good</option>
                                                <option>Damaged</option>
                                            </select>
                                            <input type="text" name="received_notes" placeholder="Notes (optional)" class="field-small w-32">
                                            <button type="submit" class="btn-sm">Confirm Received</button>
                                        </form>

                                    @else
                                        <span class="text-muted">-</span>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-muted">No orders yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection