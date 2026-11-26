<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Kasikas & Finali</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream text-ink min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-sm rounded-xl border border-stone-300 bg-white p-8">

        <div class="flex items-center gap-3 mb-8">
            <x-logo size="h-9 w-9" />
            <div>
                <div class="text-sm font-extrabold tracking-wide">KASIKAS & FINALI</div>
                <div class="text-[10px] text-muted tracking-wider">ORDERING & INVENTORY</div>
            </div>
        </div>

        <h1 class="text-2xl font-extrabold">Sign in</h1>
        <p class="page-sub mb-6">Use your Kasikas or FiNALi account.</p>
        @if (session('status'))
            <div class="alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label class="label">Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required autofocus class="field">

            <label class="label">Password</label>
            <input type="password" name="password" required class="field">

            @error('username')
                <p class="alert-error">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn-block">Sign In</button>
        </form>

        <p class="mt-4 text-center text-sm text-muted">
            No account yet?
            <a href="{{ route('register') }}" class="font-semibold text-brand hover:underline">Request an account</a>
        </p>
    </div>

</body>
</html>