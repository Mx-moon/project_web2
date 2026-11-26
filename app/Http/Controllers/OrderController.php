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
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Show the Orders page
    public function index()
    {
        $user = request()->user();

        // Start with all orders, then keep only the ones this person should see
        $query = Order::with(['product', 'fromLocation', 'toLocation']);

        if ($user->role === 'kasikas') {
            $query->where('to_location_id', $user->location_id);   // orders for their showroom
        }

        if ($user->role === 'finali') {
            $query->where('from_location_id', $user->location_id); // orders from their warehouse
        }

        // Count orders per status for the tabs (before the filters are applied)
        $counts = (clone $query)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Filter: status tab
        $status = request('status');
        if (in_array($status, ['Pending', 'Preparing', 'In Transit', 'Delivered', 'Rejected', 'Cancelled'])) {
            $query->where('status', $status);
        }

        // Filter: search by order number or furniture name
        $search = trim((string) request('q'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter: showroom
        if (request('showroom')) {
            $query->where('to_location_id', request('showroom'));
        }

        // Filter: date range
        $days = (int) request('days');
        if (in_array($days, [7, 30, 90])) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $orders = $query->latest()->get();

        $showrooms = Location::where('type', 'showroom')->orderBy('name')->get();

        // The furniture list for the order form
        $products = Product::orderBy('name')->get();

        // The warehouses for the "Ship from" dropdown
        $warehouses = Location::where('type', 'warehouse')
            ->where('active', true)
            ->orderBy('name')
            ->get();

        // How many units of each furniture the warehouses have in total
        $warehouseIds = Location::where('type', 'warehouse')->pluck('id')->toArray();
        $stockRows    = Stock::whereIn('location_id', $warehouseIds)->get();

        $available = [];
        foreach ($stockRows as $row) {
            $id = $row->product_id;

            if (! isset($available[$id])) {
                $available[$id] = 0;
            }

            $available[$id] = $available[$id] + $row->quantity;
        }

        return view('orders', compact('orders', 'products', 'warehouses', 'available', 'counts', 'showrooms'));
    }

    // Kasikas submits an order
    public function store(Request $request)
    {
        // Only Kasikas staff can place orders
        $user = $this->only('kasikas');

        // 1. Check the form
        $data = $request->validate([
            'product_id'       => ['required', 'exists:products,id'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'from_location_id' => ['required', 'exists:locations,id'],
        ]);

        // 2. How many units does the chosen warehouse have?
        $stock = Stock::where('product_id', $data['product_id'])
            ->where('location_id', $data['from_location_id'])
            ->first();

        $available = 0;
        if ($stock) {
            $available = $stock->quantity;
        }

        // 3. Not enough stock: go back with a red message
        if ($data['quantity'] > $available) {
            return back()->withInput()->withErrors([
                'quantity' => "Only {$available} units are available at that warehouse.",
            ]);
        }

        // 4. Save the order (it starts as Pending)
        $order = Order::create([
            'product_id'       => $data['product_id'],
            'quantity'         => $data['quantity'],
            'from_location_id' => $data['from_location_id'],
            'to_location_id'   => $user->location_id,
            'requested_by'     => $user->id,
        ]);

        // 5. Give it a number like ORD-0007
        $order->order_no = 'ORD-'.str_pad($order->id, 4, '0', STR_PAD_LEFT);
        $order->save();

        $this->log($order, 'Order '.$order->order_no.' requested');

        return back()->with('status', 'Order '.$order->order_no.' submitted.');
    }

    // FiNALi approves: Pending -> Preparing
    public function approve(Order $order)
    {
        $this->only('finali');
        abort_unless($order->status === 'Pending', 422);

        $order->update(['status' => 'Preparing']);
        $this->log($order, 'Order '.$order->order_no.' approved');

        return back();
    }

    // FiNALi rejects an order and must give a reason
    public function reject(Request $request, Order $order)
    {
        $this->only('finali');
        abort_unless($order->status === 'Pending', 422);

        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'max:255'],
        ]);

        $order->update([
            'status'        => 'Rejected',
            'reject_reason' => $data['reject_reason'],
        ]);

        $this->log($order, 'Order '.$order->order_no.' rejected: '.$data['reject_reason']);

        return back();
    }

    // Kasikas cancels an order before it is approved
    public function cancel(Order $order)
    {
        $this->only('kasikas');
        abort_unless($order->status === 'Pending', 422);

        $order->update(['status' => 'Cancelled']);
        $this->log($order, 'Order '.$order->order_no.' cancelled');

        return back();
    }

    // FiNALi dispatches: Preparing -> In Transit, and the stock leaves the warehouse
    public function ship(Order $order)
    {
        $user = $this->only('finali');
        abort_unless($order->status === 'Preparing', 422);

        // Do all the steps together, or none of them
        DB::beginTransaction();

        try {
            // Find the warehouse stock and lock it while we change it
            $stock = Stock::where('product_id', $order->product_id)
                ->where('location_id', $order->from_location_id)
                ->lockForUpdate()
                ->first();

            // Not enough stock: stop and show a red message
            if (! $stock || $stock->quantity < $order->quantity) {
                DB::rollBack();

                return back()->withErrors(['stock' => 'Not enough stock to dispatch.']);
            }

            // 1. Take the units out of the warehouse
            $stock->decrement('quantity', $order->quantity);

            // 2. Write it in the stock history
            StockMovement::create([
                'product_id'  => $order->product_id,
                'location_id' => $order->from_location_id,
                'order_id'    => $order->id,
                'change'      => -$order->quantity,
                'reason'      => 'dispatched',
                'user_id'     => $user->id,
            ]);

            // 3. Mark the order as on its way
            $order->update(['status' => 'In Transit', 'dispatched_at' => now()]);

            DB::commit();
        } catch (\Throwable $error) {
            DB::rollBack();
            throw $error;
        }

        $this->log($order, 'Order '.$order->order_no.' dispatched');
        $this->checkLowStock($order->product);

        return back();
    }

    // Kasikas confirms receipt: In Transit -> Delivered, and the stock enters the showroom
    public function receive(Request $request, Order $order)
    {
        $user = $this->only('kasikas');

        // Only the order for this person's showroom, and only while it is on its way
        abort_unless($order->status === 'In Transit', 422);
        abort_unless($order->to_location_id === $user->location_id, 422);

        // Check the form
        $data = $request->validate([
            'received_quantity'  => ['required', 'integer', 'min:0', 'max:'.$order->quantity],
            'received_condition' => ['required', 'in:Good,Damaged'],
            'received_notes'     => ['nullable', 'string', 'max:255'],
        ]);

        // Do all the steps together, or none of them
        DB::beginTransaction();

        try {
            // 1. Find the showroom stock (create it if this furniture was never there)
            $stock = Stock::firstOrCreate(
                ['product_id' => $order->product_id, 'location_id' => $order->to_location_id],
                ['quantity' => 0]
            );

            // 2. Add the units that really arrived
            $stock->increment('quantity', $data['received_quantity']);

            // 3. Write it in the stock history
            StockMovement::create([
                'product_id'  => $order->product_id,
                'location_id' => $order->to_location_id,
                'order_id'    => $order->id,
                'change'      => $data['received_quantity'],
                'reason'      => 'received',
                'user_id'     => $user->id,
            ]);

            // 4. Mark the order as delivered and keep what was received
            $order->update([
                'status'             => 'Delivered',
                'received_quantity'  => $data['received_quantity'],
                'received_condition' => $data['received_condition'],
                'received_notes'     => $data['received_notes'] ?? null,
                'received_at'        => now(),
            ]);

            DB::commit();
        } catch (\Throwable $error) {
            DB::rollBack();
            throw $error;
        }

        $this->log($order, 'Delivery confirmed: '.$order->order_no);

        return back();
    }

    // Only let the given role do an action, and return the logged-in person
    private function only(string $role)
    {
        $user = request()->user();

        abort_unless($user->role === $role, 403);

        return $user;
    }

    // Add a line to Notifications (with the person who did it)
    private function log(Order $order, string $title): void
    {
        $order->load(['product', 'fromLocation', 'toLocation']);

        $details = $order->product->name.' x'.$order->quantity.': '
            .$order->fromLocation->name.' → '.$order->toLocation->name;

        Alert::create([
            'type'      => 'info',
            'title'     => $title,
            'details'   => $details,
            'reference' => $order->order_no,
            'user_id'   => Auth::id(),
        ]);
    }

    // Create a red low-stock alert when the total falls under 5
    private function checkLowStock(Product $product): void
    {
        $total = $product->totalStock();

        if ($total < Product::LOW_STOCK) {
            Alert::updateOrCreate(
                ['type' => 'alert', 'title' => 'Low stock: '.$product->name],
                ['details' => $total.' units remaining across all locations', 'user_id' => Auth::id()]
            );
        }
    }
}