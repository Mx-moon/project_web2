@props(['name' => '', 'photo' => null, 'size' => 'h-12 w-12'])

@php
    $file = 'images/furniture/'.\Illuminate\Support\Str::slug($name).'.jpg';
    $src  = $photo ?: (file_exists(public_path($file)) ? asset($file) : null);
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $name }}" class="{{ $size }} shrink-0 rounded-lg bg-cream object-cover">
@else
    <div class="{{ $size }} shrink-0 rounded-lg bg-cream text-muted flex items-center justify-center">
        <svg class="h-1/2 w-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/>
            <path d="M2 14a2 2 0 0 1 4 0v1h12v-1a2 2 0 0 1 4 0v4H2z"/>
            <path d="M6 18v2M18 18v2"/>
        </svg>
    </div>
@endif