<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    public const LOW_STOCK = 5;

    protected $guarded = [];

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk(config('filesystems.photo_disk'));

        return $disk->url($this->photo);
    }

    public function totalStock(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }
}