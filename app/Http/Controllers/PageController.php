<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PageController extends Controller
{

    // DASHBOARD (Overview page)
    public function overview()
    {
        // The person who is logged in
        $user = request()->user();

        // The ids of all warehouses and all showrooms
        $warehouseIds = Location::where('type', 'warehouse')->pluck('id')->toArray();
        $showroomIds  = Location::where('type', 'showroom')->pluck('id')->toArray();

        // These two lists are filled in the loop below
        $stocks   = []; // one row for each furniture: name, warehouse units, showroom units, status
        $critical = []; // furniture that is out of stock or almost out of stock

        $products = Product::with('stocks')->orderBy('name')->get();

        foreach ($products as $product) {

            // Add up the units in the warehouses and in the showrooms
            $warehouseUnits = 0;
            $showroomUnits  = 0;

            foreach ($product->stocks as $stock) {
                if (in_array($stock->location_id, $warehouseIds)) {
                    $warehouseUnits = $warehouseUnits + $stock->quantity;
                }

                if (in_array($stock->location_id, $showroomIds)) {
                    $showroomUnits = $showroomUnits + $stock->quantity;
                }
            }

            $totalUnits = $warehouseUnits + $showroomUnits;

            // Decide the status of this furniture
            if ($totalUnits == 0) {
                $status = 'Out of stock';
                $critical[] = [$product->name, $product->sku, $totalUnits];
            } elseif ($totalUnits < Product::LOW_STOCK) {
                $status = 'Low stock';
                $critical[] = [$product->name, $product->sku, $totalUnits];
            } elseif ($showroomUnits == 0 && $warehouseUnits >= Product::LOW_STOCK) {
                $status = 'Restock showroom';
            } else {
                $status = 'OK';
            }

            $stocks[] = [$product->name, $warehouseUnits, $showroomUnits, $status];
        }

        // Most and least ordered furniture (counts delivered orders only)
        $ordered = Order::forUser($user)
            ->where('status', 'Delivered')
            ->selectRaw('product_id, SUM(quantity) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $sales = [];
        foreach ($products as $p) {
            $sales[] = [$p->name, (int) ($ordered[$p->id] ?? 0)];
        }

        usort($sales, fn ($a, $b) => $b[1] <=> $a[1]);

        $topSelling   = array_slice($sales, 0, 3);
        $leastSelling = array_slice(array_reverse($sales), 0, 3);

        // The three number cards. forUser() only counts the orders this person may see.
        $pendingCount   = Order::forUser($user)->where('status', 'Pending')->count();
        $preparingCount = Order::forUser($user)->where('status', 'Preparing')->count();
        $inTransitCount = Order::forUser($user)->where('status', 'In Transit')->count();

        $stats = [
            ['Waiting for Approval', $pendingCount, 'orders'],
            ['Being Prepared', $preparingCount, 'orders'],
            ['On the Way', $inTransitCount, 'deliveries'],
        ];

        // The 5 newest orders for the "Recent Orders" list
        $latestOrders = Order::forUser($user)
            ->with(['product', 'toLocation'])
            ->latest()
            ->take(5)
            ->get();

        $recent = [];

        foreach ($latestOrders as $order) {
            $text = $order->quantity.' '.$order->product->name.' to '.$order->toLocation->name;

            $recent[] = [$order->order_no, $text, $order->status];
        }

        // Send everything to the page: resources/views/overview.blade.php
        return view('overview', [
            'firstName'    => $user->name,
            'role'         => $user->role,
            'stats'        => $stats,
            'stocks'       => $stocks,
            'critical'     => $critical,
            'recent'       => $recent,
            'topSelling'   => $topSelling,
            'leastSelling' => $leastSelling,
        ]);
    }


    // CATALOG page
    public function catalog()
    {
        $query = Product::query();

        // Search by name or SKU
        $search = trim((string) request('q'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category
        if (request('category')) {
            $query->where('category', request('category'));
        }

        // Sort
        match (request('sort')) {
            'price_low'  => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'name_desc'  => $query->orderByDesc('name'),
            default      => $query->orderBy('name'),
        };

        $products   = $query->get();
        $categories = Product::select('category')->distinct()->orderBy('category')->pluck('category');

        // Turn each furniture into one row for the table
        $items = [];

        foreach ($products as $product) {
            $items[] = [
                $product->sku,
                $product->name,
                $product->category,
                $product->dimensions,
                $product->price,
                $product->photo_url,
                $product->id,
            ];
        }

        return view('catalog', ['items' => $items, 'categories' => $categories]);
    }

    // AVAILABILITY page
    public function availability()
    {
        // All active warehouses and showrooms
        $locations = Location::where('active', true)
            ->orderByDesc('type')
            ->orderBy('name')
            ->get();

        // Build a list like: 'Sofa' => ['Warehouse' => 14, 'Showroom 1' => 0, ...]
        $products = [];

        foreach (Product::with('stocks')->orderBy('name')->get() as $product) {
            $row = [];

            foreach ($locations as $location) {
                // Look for the stock of this furniture at this location. No stock row means 0.
                $quantity = 0;

                foreach ($product->stocks as $stock) {
                    if ($stock->location_id == $location->id) {
                        $quantity = $stock->quantity;
                    }
                }

                $row[$location->name] = $quantity;
            }

            $products[$product->name] = $row;
        }

        // "all" (or nothing picked) shows every furniture
        $selectedName = request('product');
        $showAll      = ($selectedName === null || $selectedName === '' || $selectedName === 'all' || ! isset($products[$selectedName]));

        // The 10 latest stock changes (of everything, or of the picked furniture)
        $historyQuery = StockMovement::with(['location', 'user', 'product'])->latest()->take(10);

        if (! $showAll) {
            $historyQuery->whereHas('product', function ($query) use ($selectedName) {
                $query->where('name', $selectedName);
            });
        }

        return view('availability', [
            'products'    => $products,
            'threshold'   => Product::LOW_STOCK,
            'history'     => $historyQuery->get(),
            'showAll'     => $showAll,
            'canAdd'      => in_array(request()->user()->role, ['admin', 'finali']),
            'productList' => Product::orderBy('name')->get(['id', 'name']),
            'warehouses'  => Location::where('type', 'warehouse')->where('active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    // Add stock to a warehouse (admin or FiNALi)
    public function addStock(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin', 'finali']), 403);

        $data = $request->validate([
            'product_id'  => ['required', 'exists:products,id'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('type', 'warehouse')],
            'quantity'    => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        // FiNALi staff can only add to their own warehouse
        if ($user->role === 'finali' && (int) $data['location_id'] !== (int) $user->location_id) {
            return back()->withErrors(['location_id' => 'You can only add stock to your own warehouse.'])->withInput();
        }

        DB::transaction(function () use ($data, $user) {
            $stock = Stock::firstOrCreate(
                ['product_id' => $data['product_id'], 'location_id' => $data['location_id']],
                ['quantity' => 0]
            );

            $stock->increment('quantity', $data['quantity']);

            StockMovement::create([
                'product_id'  => $data['product_id'],
                'location_id' => $data['location_id'],
                'order_id'    => null,
                'change'      => $data['quantity'],
                'reason'      => 'added',
                'user_id'     => $user->id,
            ]);
        });

        $product  = Product::find($data['product_id']);
        $location = Location::find($data['location_id']);

        Alert::create([
            'type'    => 'info',
            'title'   => 'Stock added',
            'details' => '+'.$data['quantity'].' '.$product->name.' at '.$location->name,
            'user_id' => $user->id,
        ]);

        return redirect()->route('availability', ['product' => $product->name])
            ->with('status', $data['quantity'].' units of '.$product->name.' added to '.$location->name.'.');
    }

    // NOTIFICATIONS page
    public function notifications(Request $request)
    {
        $date = $this->validDate($request->input('date'));

        $query = Alert::with('user')->latest('updated_at');

        if ($date) {
            // A date was picked: only that day
            $query->whereDate('updated_at', $date);
        } else {
            // No date: only the 30 newest
            $query->take(30);
        }

        $items = [];

        foreach ($query->get() as $alert) {
            // Old alerts have no person, so they show "System"
            $by = 'System';
            if ($alert->user) {
                $by = $alert->user->name.' ('.ucfirst($alert->user->role).')';
            }

            $items[] = [$alert->type, $alert->title, $alert->details, $alert->updated_at->format('M j, Y g:i A'), $by];
        }

        return view('notifications', [
            'items'     => $items,
            'date'      => $date,
            'canExport' => $request->user()->role === 'admin',
        ]);
    }

    // Download the notifications as a CSV file (admin only)
    public function exportNotifications(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $date  = $this->validDate($request->input('date'));
        $query = Alert::with('user')->latest('updated_at');

        if ($date) {
            $query->whereDate('updated_at', $date);
        }

        $alerts   = $query->get();
        $fileName = 'notifications-'.($date ?? now()->format('Y-m-d')).'.csv';

        return response()->streamDownload(function () use ($alerts) {
            $file = fopen('php://output', 'w');

            // Makes Excel show special characters (like the arrow) correctly
            fwrite($file, "\xEF\xBB\xBF");

            fputcsv($file, ['Date', 'Title', 'Details', 'By', 'Type']);

            foreach ($alerts as $alert) {
                $by = 'System';
                if ($alert->user) {
                    $by = $alert->user->name.' ('.ucfirst($alert->user->role).')';
                }

                fputcsv($file, [
                    $alert->updated_at->format('M j, Y g:i A'),
                    $alert->title,
                    $alert->details,
                    $by,
                    $alert->type === 'alert' ? 'Alert' : 'Info',
                ]);
            }

            fclose($file);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    // Accept only a real date like 2026-11-30, otherwise nothing
    private function validDate($value): ?string
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value)) {
            return $value;
        }

        return null;
    }

    // REPORTS page
    public function reports()
    {
        // 1. Average hours from "order placed" to "dispatched"
        $dispatchedOrders = Order::whereNotNull('dispatched_at')->get();

        $totalHours = 0;

        foreach ($dispatchedOrders as $order) {
            $totalHours = $totalHours + $order->created_at->diffInHours($order->dispatched_at);
        }

        if ($dispatchedOrders->count() > 0) {
            $averageHours = round($totalHours / $dispatchedOrders->count(), 1);
        } else {
            $averageHours = 0;
        }

        // 2. Percent of deliveries that arrived in good condition
        $deliveredOrders = Order::where('status', 'Delivered')->get();

        $goodCount = 0;

        foreach ($deliveredOrders as $order) {
            if ($order->received_condition === 'Good') {
                $goodCount = $goodCount + 1;
            }
        }

        if ($deliveredOrders->count() > 0) {
            $goodPercent = round($goodCount / $deliveredOrders->count() * 100, 1);
        } else {
            $goodPercent = 0;
        }

        // 3. Average days between two orders
        $orderDates = Order::orderBy('created_at')->pluck('created_at');

        if ($orderDates->count() > 1) {
            $totalDays   = $orderDates->first()->diffInDays($orderDates->last());
            $averageDays = round($totalDays / ($orderDates->count() - 1), 1);
        } else {
            $averageDays = 0;
        }

        // The three number cards
        $stats = [
            ['Average Dispatch Time', $averageHours.' hrs', 'from order to dispatch'],
            ['Delivered in Good Condition', $goodPercent.'%', 'of deliveries'],
            ['Days Between Orders', $averageDays.' days', 'on average'],
        ];

        // Number of orders in each of the last 6 months (for the bar chart)
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd   = $monthStart->copy()->endOfMonth();

            $months[$monthStart->format('M')] = Order::whereBetween('created_at', [$monthStart, $monthEnd])->count();
        }

        return view('reports', ['stats' => $stats, 'months' => $months]);
    }

    // Download all orders as a CSV file (opens in Excel)
    public function export()
    {
        $orders = Order::with(['product', 'fromLocation', 'toLocation'])->latest()->get();

        $fileName = 'orders-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($orders) {
            $file = fopen('php://output', 'w');

            // First row: the column titles
            fputcsv($file, ['Order No.', 'Product', 'Qty', 'From', 'Deliver To', 'Date', 'Status']);

            // One row for each order
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_no,
                    $order->product->name,
                    $order->quantity,
                    $order->fromLocation->name,
                    $order->toLocation->name,
                    $order->created_at->format('M j, Y'),
                    $order->status,
                ]);
            }

            fclose($file);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    // SETTINGS page (admin only)
    public function settings()
    {
        // Anyone who is not the admin gets the "403 Forbidden" page
        abort_unless(request()->user()->role === 'admin', 403);

        $allLocations = Location::orderByDesc('type')->orderBy('name')->get();

        $locations = [];

        foreach ($allLocations as $location) {
            // Show a dash when there is no address
            if ($location->address) {
                $address = $location->address;
            } else {
                $address = '-';
            }

            $locations[] = [$location->name, ucfirst($location->type), $address, $location->id, $location->address];
        }

        return view('settings', [
            'locations' => $locations,
            'users'     => User::with('location')->orderBy('name')->get(),
        ]);
    }

    // Admin adds a new warehouse or showroom
    public function storeLocation(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100', 'unique:locations,name'],
            'type'    => ['required', 'in:warehouse,showroom'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        Location::create($data + ['active' => true]);

        return redirect()->route('settings')->with('status', $data['name'].' was added.');
    }

    // Admin changes the name and address of a location
    public function updateLocation(Request $request, Location $location)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $location->update($data);

        return redirect()->route('settings')->with('status', $location->name.' was updated.');
    }

    // Admin deletes a location that nothing uses
    public function destroyLocation(Location $location)
    {
        abort_unless(request()->user()->role === 'admin', 403);

        $hasUsers   = User::where('location_id', $location->id)->exists();
        $hasOrders  = Order::where('from_location_id', $location->id)->orWhere('to_location_id', $location->id)->exists();
        $hasHistory = StockMovement::where('location_id', $location->id)->exists();
        $hasStock   = Stock::where('location_id', $location->id)->where('quantity', '>', 0)->exists();

        if ($hasUsers || $hasOrders || $hasHistory || $hasStock) {
            return back()->withErrors(['delete' => $location->name.' is still in use (users, orders or stock), so it cannot be deleted.']);
        }

        // Remove its empty stock rows, then the location
        Stock::where('location_id', $location->id)->delete();

        $name = $location->name;
        $location->delete();

        return back()->with('status', $name.' was deleted.');
    }
}