<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with('items.menuItem', 'restaurant', 'user')
            ->latest()
            ->paginate(15);
        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'type' => 'required|in:dine_in,takeaway,delivery',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $orderNumber = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
        
        $order = Order::create([
            'restaurant_id' => $validated['restaurant_id'],
            'table_id' => $validated['table_id'] ?? null,
            'user_id' => auth()->id(),
            'order_number' => $orderNumber,
            'type' => $validated['type'],
            'status' => 'pending',
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'notes' => $validated['notes'] ?? null,
        ]);

        $subtotal = 0;
        foreach ($validated['items'] as $item) {
            $menuItem = \App\Models\MenuItem::findOrFail($item['menu_item_id']);
            $itemSubtotal = $menuItem->price * $item['quantity'];
            
            OrderItem::create([
                'order_id' => $order->id,
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'price' => $menuItem->price,
                'subtotal' => $itemSubtotal,
            ]);
            
            $subtotal += $itemSubtotal;
        }

        $tax = $subtotal * 0.1; // 10% adó
        $total = $subtotal + $tax;

        $order->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'estimated_completion_time' => now()->addMinutes(30),
        ]);

        return response()->json($order->load('items.menuItem'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::with('items.menuItem', 'restaurant', 'payment')
            ->findOrFail($id);
        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $order->update($validated);
        return response()->json($order);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Order::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    /**
     * Get user's orders
     */
    public function userOrders()
    {
        $orders = Order::where('user_id', auth()->id())
            ->with('items.menuItem', 'restaurant')
            ->latest()
            ->paginate(15);
        return response()->json($orders);
    }

    /**
     * Update order status (admin only)
     */
    public function updateStatus(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,served,completed,cancelled',
        ]);

        $order->update($validated);
        return response()->json($order);
    }
}

