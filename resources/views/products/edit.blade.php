@extends('layouts.app')
@section('title', 'Edit Furniture')

@section('content')
<div class="mb-6">
    <a href="{{ route('catalog') }}" class="back-link">&larr; Back to catalog</a>
    <h1 class="page-title mt-2">Edit Furniture</h1>
    <p class="page-sub">Change the details or the photo of {{ $product->name }}</p>
</div>

@if ($errors->any())
    <div class="alert-error max-w-2xl">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('catalog.update', $product) }}" enctype="multipart/form-data" class="form-card">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-x-4 md:grid-cols-2">
        <div>
            <label class="label">Furniture name</label>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="field">
        </div>
        <div>
            <label class="label">SKU code</label>
            <div class="field text-muted">{{ $product->sku }}</div>
        </div>
        <div>
            <label class="label">Category</label>
            <input type="text" name="category" value="{{ old('category', $product->category) }}" required class="field">
        </div>
        <div>
            <label class="label">Size</label>
            <input type="text" name="dimensions" value="{{ old('dimensions', $product->dimensions) }}" class="field">
        </div>
        <div>
            <label class="label">Price (₱)</label>
            <input type="number" name="price" min="0" step="0.01" value="{{ old('price', $product->price) }}" required class="field">
        </div>
        <div class="md:col-span-2">
            <label class="label">Photo (leave empty to keep the current one)</label>
            <div class="mb-3">
                <x-furniture-image :name="$product->name" :photo="$product->photo_url" size="h-16 w-16" />
            </div>
            <input type="file" name="photo" accept="image/*" class="file-input">
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn px-6">Save Changes</button>
        <a href="{{ route('catalog') }}" class="btn-outline">Cancel</a>
    </div>
</form>
@endsection