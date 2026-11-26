@extends('layouts.app')
@section('title', 'Reports')

@section('content')
@php
    $max = max(1, max($months));

    $summary = [
        ['Warehouse Dispatch', 'Approved orders are dispatched on time with no delays.'],
        ['Showroom Deliveries', 'Deliveries arrive about 1.8 hours ahead of the target time.'],
    ];
@endphp

<div class="flex items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Reports</h1>
    </div>
    <a href="{{ route('reports.export') }}" class="btn">Export Data (.CSV)</a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    @foreach ($stats as [$label, $value, $note])
        <div class="card">
            <div class="stat-label">{{ $label }}</div>
            <div class="mt-1 flex items-baseline gap-2">
                <span class="text-4xl font-extrabold">{{ $value }}</span>
                <span class="text-sm text-muted">{{ $note }}</span>
            </div>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <div class="card lg:col-span-2">
        <h2 class="font-bold text-lg mb-6">Orders per Month</h2>
        <div class="flex items-end gap-4">
            @foreach ($months as $month => $value)
                <div class="flex-1 flex flex-col items-center">
                    <div class="flex h-64 w-full flex-col items-center justify-end">
                        <span class="mb-2 text-sm font-bold">{{ $value }}</span>
                        {{-- Height is set by resources/js/app.js from data-height --}}
                        <div class="w-6 rounded-t bg-brand" data-height="{{ round($value / $max * 200) }}"></div>
                    </div>
                    <span class="mt-3 text-xs text-muted">{{ $month }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card self-start">
        <h2 class="card-title">Delivery Summary</h2>
        <div class="space-y-3">
            @foreach ($summary as [$title, $text])
                <div class="rounded-lg bg-cream p-4">
                    <div class="text-sm font-bold">{{ $title }}</div>
                    <div class="mt-1 text-xs text-muted">{{ $text }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection