@extends('layouts.app')
@section('title', $user->exists ? 'Edit User' : 'Add User')

@section('content')
@php
    $roles = ['kasikas' => 'Kasikas (showroom staff)', 'finali' => 'FiNALi (warehouse staff)', 'admin' => 'Admin'];
@endphp

<div class="mb-6">
    <a href="{{ route('users.index') }}" class="back-link">&larr; Back to users</a>
    <h1 class="page-title mt-2">{{ $user->exists ? 'Edit User' : 'Add User' }}</h1>
    <p class="page-sub">Kasikas staff belong to a showroom. FiNALi staff belong to a warehouse. Admins need no location.</p>
</div>

@if ($errors->any())
    <div class="alert-error max-w-2xl">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="form-card">
    @csrf
    @if ($user->exists)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 gap-x-4 md:grid-cols-2">
        <div>
            <label class="label">Full name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="field">
        </div>
        <div>
            <label class="label">Username</label>
            <input type="text" name="username" value="{{ old('username', $user->username) }}" required class="field">
        </div>
        <div class="md:col-span-2">
            <label class="label">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="field">
        </div>
        <div>
            <label class="label">Role</label>
            <select name="role" class="field">
                @foreach ($roles as $value => $text)
                    <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $text }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Location</label>
            <select name="location_id" class="field">
                <option value="">No location (admin)</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}" @selected((string) old('location_id', $user->location_id) === (string) $location->id)>
                        {{ $location->name }} ({{ ucfirst($location->type) }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Password{{ $user->exists ? ' (leave empty to keep the current one)' : '' }}</label>
            <input type="password" name="password" @unless ($user->exists) required @endunless class="field">
        </div>
        <div>
            <label class="label">Confirm password</label>
            <input type="password" name="password_confirmation" @unless ($user->exists) required @endunless class="field">
        </div>
    </div>

    <p class="mb-4 text-xs text-muted">The password needs at least 8 characters.</p>

    <div class="flex gap-3">
        <button type="submit" class="btn px-6">{{ $user->exists ? 'Save Changes' : 'Create Account' }}</button>
        <a href="{{ route('users.index') }}" class="btn-outline">Cancel</a>
    </div>
</form>
@endsection
