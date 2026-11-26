@props(['size' => 'h-8 w-8'])

@if (file_exists(public_path('images/logo.png')))
    <img src="{{ asset('images/logo.png') }}" alt="Kasikas & Finali" class="{{ $size }} shrink-0 rounded-full object-cover">
@else
    <div class="{{ $size }} shrink-0 rounded-full bg-brand text-white text-[10px] font-bold flex items-center justify-center">logo</div>
@endif
