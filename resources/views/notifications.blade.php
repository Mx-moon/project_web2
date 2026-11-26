@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-sub">
            @if ($date)
                Showing notifications from {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}
            @else
                Showing the latest 30 notifications
            @endif
        </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <form method="GET" action="{{ route('notifications') }}" class="flex flex-wrap items-center gap-2">
            <input type="date" name="date" value="{{ $date }}"
                   class="rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand">
            <button type="submit" class="btn-sm">Show</button>

            @if ($date)
                <a href="{{ route('notifications') }}" class="btn-sm-outline">Clear</a>
            @endif
        </form>

        @if ($canExport)
            <a href="{{ route('notifications.export', $date ? ['date' => $date] : []) }}" class="btn-sm-outline">Export CSV</a>
        @endif
    </div>
</div>

<div class="rounded-xl border border-stone-300 bg-white px-6 py-2">
    @forelse ($items as [$type, $title, $details, $time, $by])
        <div class="flex items-start justify-between gap-4 border-b border-stone-200 py-4 last:border-0">
            <div class="flex items-start gap-3">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $type === 'alert' ? 'bg-red-600' : 'bg-brand' }}"></span>
                <div>
                    <div class="text-sm font-bold">{{ $title }}</div>
                    <div class="text-xs text-muted">{{ $details }}</div>
                    <div class="text-xs text-muted">By {{ $by }}</div>
                </div>
            </div>
            <span class="text-xs text-muted whitespace-nowrap">{{ $time }}</span>
        </div>
    @empty
        <p class="py-6 text-center text-sm text-muted">
            @if ($date)
                No notifications on {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}.
            @else
                No notifications yet.
            @endif
        </p>
    @endforelse
</div>
@endsection