<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $orders = Order::with('items.menuItem', 'restaurant', 'user')
                ->latest()
                ->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $orders,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a rendelések lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'restaurant_id' => 'required|exists:restaurants,id',
                'table_id' => 'nullable|exists:restaurant_tables,id',
                'type' => 'required|in:dine_in,takeaway,delivery',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1|max:100',
                'notes' => 'nullable|string|max:500',
            ]);

            // Validáció: dine_in típusnál kötelező az asztal
            if ($validated['type'] === 'dine_in' && !$validated['table_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Helyben történő rendeléshez szükséges az asztal kiválasztása.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $orderNumber = 'ORD-' . date('YmdHis') . '-' . Str::random(6);
            
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
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                
                if (!$menuItem->is_available) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Az ' . $menuItem->name . ' étel nem elérhető.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

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

            return response()->json([
                'success' => true,
                'message' => 'Rendelés sikeresen létrehozva.',
                'data' => $order->load('items.menuItem'),
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validációs hiba.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a rendelés létrehozásakor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $order = Order::with('items.menuItem', 'restaurant', 'payment')
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $order,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A rendelés nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a rendelés lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $order = Order::findOrFail($id);
            
            $validated = $request->validate([
                'notes' => 'nullable|string|max:500',
            ]);

            $order->update($validated);
            
            return response()->json([
                'success' => true,
                'message' => 'Rendelés sikeresen frissítve.',
                'data' => $order,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A rendelés nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a rendelés frissítésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get user's orders
     */
    public function userOrders()
    {
        try {
            $orders = Order::where('user_id', auth()->id())
                ->with('items.menuItem', 'restaurant')
                ->latest()
                ->paginate(15);
            
            return response()->json([
                'success' => true,
                'data' => $orders,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a rendelések lekérésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update order status (admin only)
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            $order = Order::findOrFail($id);
            
            $validated = $request->validate([
                'status' => 'required|in:pending,confirmed,preparing,ready,served,completed,cancelled',
            ]);

            // Validáció: csak pending-ből lehet cancelled
            if ($order->status !== 'pending' && $validated['status'] === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Csak feldolgozás alatt álló rendelés vonható vissza.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $order->update($validated);
            
            // Ha completed, akkor jelöljük a befejezési időt
            if ($validated['status'] === 'completed') {
                $order->update(['completed_at' => now()]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Rendelés státusza sikeresen frissítve.',
                'data' => $order,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'A rendelés nem található.',
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hiba a státusz frissítésekor.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
    


