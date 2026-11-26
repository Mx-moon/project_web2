@extends('layouts.app')
@section('title', 'Users')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title mt-2">Users</h1>
    </div>
</div>

@if (session('status'))
    <div class="alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

@php $pending = $users->where('status', 'pending'); @endphp

{{-- Account requests waiting for the admin --}}
@if ($pending->isNotEmpty())
    <div class="card mb-6 border-orange-300">
        <h2 class="card-title">Pending requests ({{ $pending->count() }})</h2>
        @foreach ($pending as $p)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 py-3 text-sm">
                <div class="flex items-center gap-3">
                    <x-avatar :name="$p->name" />
                    <div>
                        <div class="font-semibold">{{ $p->name }} <span class="text-muted font-normal">({{ $p->username }})</span></div>
                        <div class="text-xs text-muted">{{ ucfirst($p->role) }} | {{ $p->location->name ?? '-' }} | {{ $p->email }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('users.approve', $p) }}">
                        @csrf
                        <button type="submit" class="btn-sm">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('users.reject', $p) }}" data-confirm="Reject the request of {{ $p->name }}?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-sm-outline">Reject</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="table-head">
                    <th class="th">Name</th>
                    <th class="th">Username</th>
                    <th class="th">Role</th>
                    <th class="th">Location</th>
                    <th class="py-2 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users->where('status', '!=', 'pending') as $u)
                    <tr class="table-row">
                        <td class="td">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$u->name" />
                                <span class="font-medium">{{ $u->name }}</span>
                            </div>
                        </td>
                        <td class="td font-mono text-xs font-bold">{{ $u->username }}</td>
                        <td class="td"><span class="badge badge-{{ $u->role }}">{{ ucfirst($u->role) }}</span></td>
                        <td class="td-muted">{{ $u->location->name ?? '-' }}</td>
                        <td class="py-3 whitespace-nowrap">
                            <a href="{{ route('users.edit', $u) }}" class="btn-link">Edit</a>
                            @if ($u->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $u) }}" class="inline"
                                      data-confirm="Are you sure you want to delete {{ $u->name }}? This cannot be undone.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-link-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection