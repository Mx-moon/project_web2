<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Show the "Add Furniture" form
    public function create()
    {
        $this->allow();

        $warehouses = Location::where('type', 'warehouse')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('products.create', compact('warehouses'));
    }

    // Save new furniture
    public function store(Request $request)
    {
        $this->allow();

        // 1. Check the form (the SKU is made automatically, so it is not asked for)
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'category'     => ['required', 'string', 'max:50'],
            'dimensions'   => ['nullable', 'string', 'max:50'],
            'price'        => ['required', 'numeric', 'min:0'],
            'photo'        => ['nullable', 'image', 'max:2048'],
            'warehouse_id' => ['nullable', 'exists:locations,id'],
            'quantity'     => ['nullable', 'integer', 'min:0'],
        ]);

        // 2. Save the photo (only if one was chosen)
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('products', config('filesystems.photo_disk'));
        }

        // 3. Save the furniture
        $product = Product::create([
            'name'       => $data['name'],
            'sku'        => $this->makeSku($data['name']),
            'category'   => $data['category'],
            'dimensions' => $data['dimensions'] ?? null,
            'price'      => $data['price'],
            'photo'      => $photoPath,
        ]);

        // 4. Save the starting stock in the chosen warehouse
        if (! empty($data['warehouse_id'])) {
            $startQuantity = $data['quantity'] ?? 0;

            Stock::create([
                'product_id'  => $product->id,
                'location_id' => $data['warehouse_id'],
                'quantity'    => $startQuantity,
            ]);
        }

        // 5. Add a line to Notifications
        Alert::create([
            'type'    => 'info',
            'title'   => 'New furniture added',
            'details' => $product->name.' was added to the catalog',
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('catalog')->with('status', $product->name.' was added to the catalog.');
    }

    // Show the "Edit Furniture" form
    public function edit(Product $product)
    {
        $this->allow();

        return view('products.edit', compact('product'));
    }

    // Save the changes to furniture (the SKU never changes)
    public function update(Request $request, Product $product)
    {
        $this->allow();

        // 1. Check the form
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'category'   => ['required', 'string', 'max:50'],
            'dimensions' => ['nullable', 'string', 'max:50'],
            'price'      => ['required', 'numeric', 'min:0'],
            'photo'      => ['nullable', 'image', 'max:2048'],
        ]);

        // 2. Keep the old photo unless a new one was chosen
        $photoPath = $product->photo;

        if ($request->hasFile('photo')) {
            $disk = config('filesystems.photo_disk');

            // Delete the old photo, then save the new one
            if ($product->photo) {
                Storage::disk($disk)->delete($product->photo);
            }

            $photoPath = $request->file('photo')->store('products', $disk);
        }

        // 3. Save the changes
        $product->update([
            'name'       => $data['name'],
            'category'   => $data['category'],
            'dimensions' => $data['dimensions'] ?? null,
            'price'      => $data['price'],
            'photo'      => $photoPath,
        ]);

        // 4. Add a line to Notifications
        Alert::create([
            'type'    => 'info',
            'title'   => 'Furniture updated',
            'details' => $product->name.' was edited',
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('catalog')->with('status', $product->name.' was updated.');
    }

    // Remove furniture
    public function destroy(Product $product)
    {
        $this->allow();

        // Furniture with order history stays, so old orders keep their details
        $hasOrders    = Order::where('product_id', $product->id)->exists();
        $hasMovements = StockMovement::where('product_id', $product->id)->exists();

        if ($hasOrders || $hasMovements) {
            return back()->withErrors(['delete' => $product->name.' has order history, so it cannot be removed.']);
        }

        // Delete the photo file
        if ($product->photo) {
            Storage::disk(config('filesystems.photo_disk'))->delete($product->photo);
        }

        // Delete the furniture, then add a line to Notifications
        $name = $product->name;
        $product->delete();

        Alert::create([
            'type'    => 'info',
            'title'   => 'Furniture removed',
            'details' => $name.' was removed from the catalog',
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('catalog')->with('status', $name.' was removed from the catalog.');
    }

    // Make the SKU: 3 letters of the furniture type + a running number (e.g. TAB-001)
    private function makeSku(string $name): string
    {
        // Remove anything in brackets, then take the last word of the name
        $clean = trim(preg_replace('/\(.*?\)/', '', $name));
        $words = preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: ['Item'];

        // First 3 letters of that word, in capitals
        $type = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', end($words)), 0, 3)) ?: 'ITM';

        // The next free number for this type
        $n = Product::where('sku', 'like', $type.'-%')->count() + 1;

        do {
            $sku = $type.'-'.str_pad($n, 3, '0', STR_PAD_LEFT);
            $n++;
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    // Only admin and FiNALi staff may add, edit or remove furniture
    private function allow(): void
    {
        $role = request()->user()->role;

        abort_unless($role === 'admin' || $role === 'finali', 403);
    }
}