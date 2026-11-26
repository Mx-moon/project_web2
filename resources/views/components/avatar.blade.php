@props(['name' => '', 'photo' => null, 'size' => 'h-8 w-8', 'text' => 'text-[11px]'])

@php
    $words    = preg_split('/\s+/', trim($name));
    $initials = strtoupper(mb_substr($words[0] ?? '', 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
@endphp

@if ($photo)
    <img src="{{ $photo }}" alt="{{ $name }}" class="{{ $size }} shrink-0 rounded-full object-cover">
@else
    <div class="{{ $size }} {{ $text }} shrink-0 rounded-full bg-brand font-bold text-white flex items-center justify-center" title="{{ $name }}">{{ $initials }}</div>
@endif