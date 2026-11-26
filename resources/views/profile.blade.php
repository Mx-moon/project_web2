@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
@php $user = auth()->user(); @endphp

<div class="mb-6">
    <h1 class="page-title">My Profile</h1>
</div>

@if (session('status'))
    <div class="alert-success max-w-2xl">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert-error max-w-2xl">{{ $errors->first() }}</div>
@endif

<div class="form-card">
    <div class="flex items-center gap-5">
        <x-avatar :name="$user->name" size="h-24 w-24" text="text-3xl" />
        <div>
            <div class="text-xl font-extrabold">{{ $user->name }}</div>
            <div class="text-sm text-muted">{{ ucfirst($user->role) }} | Username: {{ $user->username }}</div>
            @if ($user->location)
                <div class="text-sm text-muted">{{ $user->location->name }}</div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" class="mt-6">
        @csrf
        @method('PUT')

        <label class="label">Full name</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="field">

        <label class="label">Username</label>
        <input type="text" name="username" value="{{ old('username', $user->username) }}" required class="field">

        <h2 class="card-title mt-2">Change password</h2>
        <p class="note mb-3">Leave these empty if you don't want to change your password.</p>

        <label class="label">Current password</label>
        <input type="password" name="current_password" class="field">

        <label class="label">New password</label>
        <input type="password" name="password" class="field">

        <label class="label">Confirm new password</label>
        <input type="password" name="password_confirmation" class="field">

        <button type="submit" class="btn px-6">Save Changes</button>
    </form>

</div>
@endsection
