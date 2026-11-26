@extends('layouts.app')
@section('title', 'Add Furniture')

@section('content')
<div class="mb-6">
    <a href="{{ route('catalog') }}" class="back-link">&larr; Back to catalog</a>
    <h1 class="page-title mt-2">Add Furniture</h1>
    <p class="page-sub">Register a new item in the catalog</p>
</div>

@if ($errors->any())
    <div class="alert-error max-w-2xl">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('catalog.store') }}" enctype="multipart/form-data" class="form-card">
    @csrf

    <div class="grid grid-cols-1 gap-x-4 md:grid-cols-2">
        <div>
            <label class="label">Furniture name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="field">
        </div>
        <div>
            <label class="label">SKU code</label>
            <div class="field text-muted">Made automatically when you save</div>
        </div>
        <div>
            <label class="label">Category</label>
            <input type="text" name="category" value="{{ old('category') }}" required placeholder="e.g. Seating" class="field">
        </div>
        <div>
            <label class="label">Size</label>
            <input type="text" name="dimensions" value="{{ old('dimensions') }}" placeholder="e.g. 220x95x85 cm" class="field">
        </div>
        <div>
            <label class="label">Price (₱)</label>
            <input type="number" name="price" min="0" step="0.01" value="{{ old('price') }}" required class="field">
        </div>
        <div>
            <label class="label">Starting stock</label>
            <input type="number" name="quantity" min="0" value="{{ old('quantity', 0) }}" class="field">
        </div>
        <div class="md:col-span-2">
            <label class="label">Add the starting stock to</label>
            <select name="warehouse_id" class="field">
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="label">Photo (JPG or PNG, up to 2 MB)</label>
            <input type="file" name="photo" accept="image/*" class="file-input">
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn px-6">Save Furniture</button>
        <a href="{{ route('catalog') }}" class="btn-outline">Cancel</a>
    </div>
</form>
@endsection
