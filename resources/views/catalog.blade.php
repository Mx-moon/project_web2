@extends('layouts.app')
@section('title', 'Catalog')

@section('content')
@php
    $canEdit = in_array(auth()->user()->role, ['admin', 'finali']);

    $chip = fn ($active) => 'rounded-full border px-3 py-1 text-xs font-semibold '
        . ($active ? 'border-brand bg-brand text-white' : 'border-stone-300 text-muted hover:bg-cream');
@endphp

@if (session('status'))
    <div class="alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert-error">{{ $errors->first() }}</div>
@endif

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="page-title">Catalog</h1>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('catalog') }}" class="flex flex-wrap items-center gap-2">
            @if (request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif

            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search furniture or SKU..."
                   class="w-56 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-brand">
            <button type="submit" class="btn-sm">Search</button>

            <select name="sort" data-autosubmit class="field-small py-2">
                <option value="">Name A-Z</option>
                <option value="name_desc"  @selected(request('sort') === 'name_desc')>Name Z-A</option>
                <option value="price_low"  @selected(request('sort') === 'price_low')>Price: low to high</option>
                <option value="price_high" @selected(request('sort') === 'price_high')>Price: high to low</option>
            </select>

            @if (request()->hasAny(['q', 'category', 'sort']))
                <a href="{{ route('catalog') }}" class="btn-sm-outline">Clear</a>
            @endif
        </form>

        @if ($canEdit)
            <a href="{{ route('catalog.create') }}" class="btn">+ Add Furniture</a>
        @endif
    </div>
</div>

<div class="card">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="table-head">
                    <th class="th">Furniture</th>
                    <th class="th">SKU</th>
                    <th class="th">Category</th>
                    <th class="th">Size</th>
                    <th class="py-2 font-semibold">Price</th>
                    @if ($canEdit)
                        <th class="py-2 pl-4 font-semibold">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($items as [$sku, $name, $category, $size, $price, $photo, $id])
                    <tr class="table-row">
                        <td class="td">
                            <div class="flex items-center gap-3">
                                <x-furniture-image :name="$name" :photo="$photo" size="h-12 w-12" />
                                <span class="font-medium">{{ $name }}</span>
                            </div>
                        </td>
                        <td class="td font-mono text-xs font-bold whitespace-nowrap">{{ $sku }}</td>
                        <td class="td-muted">{{ $category }}</td>
                        <td class="td-muted whitespace-nowrap">{{ $size }}</td>
                        <td class="py-3 font-bold whitespace-nowrap">₱{{ number_format($price) }}</td>
                        @if ($canEdit)
                            <td class="py-3 pl-4 whitespace-nowrap">
                                <a href="{{ route('catalog.edit', $id) }}" class="btn-link">Edit</a>
                                <form method="POST" action="{{ route('catalog.destroy', $id) }}" class="inline"
                                      data-confirm="Remove {{ $name }} from the catalog?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-link-danger">Remove</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canEdit ? 6 : 5 }}" class="py-6 text-center text-muted">No furniture matches your search.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection