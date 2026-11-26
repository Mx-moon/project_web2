<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Kasikas & Finali</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-cream text-ink min-h-screen">

@php
    $links = [
        'overview'      => 'Dashboard',
        'catalog'       => 'Catalog',
        'availability'  => 'Availability',
        'orders'        => 'Orders',
        'notifications' => 'Notifications',
        'reports'       => 'Reports',
        'settings'      => 'Settings',
    ];

    // Only the admin sees Settings
    if (auth()->user()->role !== 'admin') unset($links['settings']);
@endphp

<header class="bg-white border-b border-stone-300">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-6">

        <div class="flex items-center gap-3 py-3">
            <x-logo size="h-9 w-9" />
            <div class="hidden sm:block">
                <div class="text-sm font-extrabold tracking-wide">KASIKAS & FINALI</div>
            </div>
        </div>

        {{-- Menu for large screens --}}
        <nav class="hidden gap-6 text-sm md:flex">
            @foreach ($links as $route => $label)
                <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'nav-link-active' : 'nav-link' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-3">
            <div class="hidden text-right leading-tight sm:block">
                <div class="text-xs font-semibold">{{ auth()->user()->name }}</div>
                <div class="text-[10px] text-muted">{{ ucfirst(auth()->user()->role) }}</div>
            </div>
            <a href="{{ route('profile') }}" title="My profile">
               <x-avatar :name="auth()->user()->name" />
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-full bg-brand px-3 py-1.5 text-[10px] font-bold text-white hover:opacity-90">Log out</button>
            </form>

            {{-- Menu button for phones (script is in resources/js/app.js) --}}
            <button type="button" id="menu-button" aria-label="Open menu" class="rounded-lg border border-stone-300 p-2 md:hidden">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Menu for phones --}}
    <nav id="mobile-menu" class="hidden border-t border-stone-200 px-6 py-2 md:hidden">
        @foreach ($links as $route => $label)
            <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'mobile-link-active' : 'mobile-link' }}">{{ $label }}</a>
        @endforeach
    </nav>
</header>

<main class="mx-auto max-w-7xl px-6 py-8">
    @yield('content')
</main>

</body>
</html>
