<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request an account | Kasikas & Finali</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream text-ink min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md rounded-xl border border-stone-300 bg-white p-8">

        <div class="flex items-center gap-3 mb-6">
            <x-logo size="h-9 w-9" />
            <div>
                <div class="text-sm font-extrabold tracking-wide">KASIKAS & FINALI</div>
                <div class="text-[10px] text-muted tracking-wider">ORDERING & INVENTORY</div>
            </div>
        </div>

        <h1 class="text-2xl font-extrabold">Request an account</h1>
        <p class="page-sub mb-6">The admin must approve your account before you can sign in.</p>

        @if ($errors->any())
            <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <label class="label">Full name</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus class="field">

            <label class="label">Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required class="field">

            <label class="label">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="field">

            <label class="label">I work for</label>
            <select name="role" required class="field">
                <option value="kasikas" @selected(old('role') === 'kasikas')>Kasikas (showroom)</option>
                <option value="finali"  @selected(old('role') === 'finali')>FiNALi (warehouse)</option>
            </select>

            <label class="label">Location</label>
            <select name="location_id" required class="field">
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>
                        {{ $location->name }} ({{ ucfirst($location->type) }})
                    </option>
                @endforeach
            </select>

            <label class="label">Password</label>
            <input type="password" name="password" required class="field">

            <label class="label">Confirm password</label>
            <input type="password" name="password_confirmation" required class="field">

            <button type="submit" class="btn-block">Send Request</button>
        </form>

        <p class="mt-4 text-center text-sm text-muted">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-brand hover:underline">Sign in</a>
        </p>
    </div>

</body>
</html>