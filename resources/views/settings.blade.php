@extends('layouts.app')
@section('title', 'Settings')

@section('content')
@php
    $menu = ['Locations', 'Users'];
@endphp

<div class="mb-6">
    <h1 class="page-title">System Settings</h1>
</div>

@if (session('status'))
    <div class="alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4">

    {{-- Side menu --}}
    <div class="rounded-xl border border-stone-300 bg-white p-3 self-start">
        @foreach ($menu as $i => $item)
            @if ($item === 'Users')
                <a href="{{ route('users.index') }}" class="block px-4 py-3 rounded-lg text-sm font-semibold hover:bg-cream">{{ $item }}</a>
            @else
                <div class="px-4 py-3 rounded-lg text-sm {{ $i === 0 ? 'bg-cream font-bold' : 'text-muted' }}">{{ $item }}</div>
            @endif
        @endforeach
    </div>

    <div class="lg:col-span-3 space-y-4">

        {{-- Locations list --}}
        <div class="card">
            <h2 class="card-title">Locations</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="table-head">
                            <th class="th">Name</th>
                            <th class="th">Type</th>
                            <th class="th">Address</th>
                            <th class="py-2 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($locations as [$name, $type, $address, $id, $rawAddress])
                            <tr class="table-row">
                                <td class="td font-medium">{{ $name }}</td>
                                <td class="td"><span class="badge badge-{{ strtolower($type) }}">{{ $type }}</span></td>
                                <td class="td-muted">{{ $address }}</td>
                                <td class="py-3 text-right whitespace-nowrap">
                                    <button type="button" data-modal-open="edit-location-{{ $id }}" class="btn-link">Edit</button>

                                    <form method="POST" action="{{ route('settings.locations.destroy', $id) }}" class="inline"
                                          data-confirm="Are you sure you want to delete {{ $name }}? This cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-link-danger">Delete</button>
                                    </form>

                                    <dialog id="edit-location-{{ $id }}"
                                            class="m-auto w-full max-w-md rounded-xl border border-stone-300 bg-white p-0 text-left backdrop:bg-black/50">
                                        <form method="POST" action="{{ route('settings.locations.update', $id) }}" class="p-6">
                                            @csrf
                                            @method('PUT')

                                            <div class="mb-4 flex items-center justify-between">
                                                <h2 class="card-title mb-0">Edit Location</h2>
                                                <button type="button" data-modal-close class="text-2xl leading-none text-muted hover:text-ink" aria-label="Close">&times;</button>
                                            </div>

                                            <label class="label">Name</label>
                                            <input type="text" name="name" value="{{ $name }}" required class="field">

                                            <label class="label">Type</label>
                                            <div class="field text-muted">{{ $type }}</div>

                                            <label class="label">Address</label>
                                            <input type="text" name="address" value="{{ $rawAddress }}" placeholder="Street, city" class="field">

                                            <div class="flex gap-2">
                                                <button type="submit" class="btn-block">Save Changes</button>
                                                <button type="button" data-modal-close class="btn-outline">Cancel</button>
                                            </div>
                                        </form>
                                    </dialog>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- All users --}}
        @if (auth()->user()->role === 'admin')
            <div class="card">
                <h2 class="card-title">Users</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="table-head">
                                <th class="th">Name</th>
                                <th class="th">Username</th>
                                <th class="th">Role</th>
                                <th class="th">Location</th>
                                <th class="py-2 font-semibold"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users->where('status', '!=', 'pending') as $u)
                                <tr class="table-row">
                                    <td class="td font-medium">{{ $u->name }}</td>
                                    <td class="td-muted">{{ $u->username }}</td>
                                    <td class="td"><span class="badge badge-{{ $u->role }}">{{ ucfirst($u->role) }}</span></td>
                                    <td class="td-muted">{{ $u->location->name ?? '-' }}</td>
                                    <td class="py-3 text-right whitespace-nowrap">
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
        @endif

        {{-- Add a location --}}
        <form method="POST" action="{{ route('settings.locations') }}" class="card">
            @csrf
            <h2 class="card-title">Add Location</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="label">Name</label>
                    <input type="text" name="name" required placeholder="e.g. Kasikas Showroom 3" class="field">
                </div>
                <div>
                    <label class="label">Type</label>
                    <select name="type" class="field">
                        <option value="warehouse">Warehouse</option>
                        <option value="showroom">Showroom</option>
                    </select>
                </div>
                <div>
                    <label class="label">Address</label>
                    <input type="text" name="address" placeholder="Street, city" class="field">
                </div>
            </div>

            <button type="submit" class="btn px-6">Add Location</button>
        </form>
    </div>
</div>
@endsection